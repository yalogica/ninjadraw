var YalogicaNinjaDrawPresentation = (function (exports) {
  'use strict';

  const g = yalogica_ninjadraw_presentation_global;

  const SVG_NAMESPACE = "http://www.w3.org/2000/svg";
  const CHEVRON_LEFT_ICON = [
    "m15 18-6-6 6-6"
  ];
  const CHEVRON_RIGHT_ICON = [
    "m9 18 6-6-6-6"
  ];
  const MAXIMIZE_ICON = [
    "M8 3H5a2 2 0 0 0-2 2v3",
    "M21 8V5a2 2 0 0 0-2-2h-3",
    "M3 16v3a2 2 0 0 0 2 2h3",
    "M16 21h3a2 2 0 0 0 2-2v-3"
  ];
  const MINIMIZE_ICON = [
    "M8 3v3a2 2 0 0 1-2 2H3",
    "M21 8h-3a2 2 0 0 1-2-2V3",
    "M3 16h3a2 2 0 0 1 2 2v3",
    "M16 21v-3a2 2 0 0 1 2-2h3"
  ];
  const createLucideIcon = (paths) => {
    const svg = document.createElementNS(
      SVG_NAMESPACE,
      "svg"
    );
    svg.setAttribute("viewBox", "0 0 24 24");
    svg.setAttribute("width", "18");
    svg.setAttribute("height", "18");
    svg.setAttribute("fill", "none");
    svg.setAttribute("stroke", "currentColor");
    svg.setAttribute("stroke-width", "2");
    svg.setAttribute("stroke-linecap", "round");
    svg.setAttribute("stroke-linejoin", "round");
    svg.setAttribute("aria-hidden", "true");
    svg.setAttribute("focusable", "false");
    svg.classList.add("toolbar-icon");
    for (const pathData of paths) {
      const path = document.createElementNS(
        SVG_NAMESPACE,
        "path"
      );
      path.setAttribute("d", pathData);
      svg.append(path);
    }
    return svg;
  };
  class PresentationPlayer {
    container;
    shadowRoot;
    viewport;
    sceneLayer;
    counter;
    isAnimating = false;
    activeAnimationClass = null;
    data;
    currentIndex = 0;
    resizeObserver;
    fullscreenButton;
    constructor(container, options) {
      this.container = container;
      this.data = options.presentation;
      this.shadowRoot = container.shadowRoot ?? container.attachShadow({ mode: "open" });
      const style = document.createElement("style");
      style.textContent = options.styles;
      this.viewport = document.createElement("div");
      this.viewport.className = "viewport";
      this.sceneLayer = document.createElement("div");
      this.sceneLayer.className = "scene-layer";
      this.sceneLayer.innerHTML = options.svg;
      const svgElement = this.sceneLayer.querySelector("svg");
      const backgroundColor = svgElement?.getAttribute("data-background-color") || "#ffffff";
      this.container.style.setProperty("--presentation-background-color", backgroundColor);
      this.counter = document.createElement("span");
      this.counter.className = "counter";
      this.viewport.append(this.sceneLayer);
      const root = document.createElement("div");
      root.className = "presentation";
      root.tabIndex = 0;
      root.style.setProperty(
        "--padding-top",
        `${this.data.padding.top}px`
      );
      root.style.setProperty(
        "--padding-right",
        `${this.data.padding.right}px`
      );
      root.style.setProperty(
        "--padding-bottom",
        `${this.data.padding.bottom}px`
      );
      root.style.setProperty(
        "--padding-left",
        `${this.data.padding.left}px`
      );
      const toolbar = this.createToolbar();
      root.append(this.viewport, toolbar);
      this.shadowRoot.replaceChildren(style, root);
      this.sceneLayer.style.width = `${this.data.scene.width}px`;
      this.sceneLayer.style.height = `${this.data.scene.height}px`;
      document.addEventListener("fullscreenchange", this.handleFullscreenChange);
      this.updateFullscreenButton();
      this.bindKeyboard(root);
      this.resizeObserver = new ResizeObserver(() => {
        this.applySlide(
          this.data.slides[this.currentIndex],
          false
        );
      });
      this.resizeObserver.observe(this.viewport);
      this.show(0, false);
    }
    createToolbar() {
      const toolbar = document.createElement("div");
      toolbar.className = "toolbar";
      toolbar.setAttribute("role", "toolbar");
      toolbar.setAttribute("aria-label", "Presentation controls");
      const previous = document.createElement("button");
      previous.type = "button";
      previous.className = "toolbar-button";
      previous.append(createLucideIcon(CHEVRON_LEFT_ICON));
      previous.setAttribute(
        "aria-label",
        "Previous slide"
      );
      previous.addEventListener("click", () => {
        this.previous();
      });
      const next = document.createElement("button");
      next.type = "button";
      next.className = "toolbar-button";
      next.append(createLucideIcon(CHEVRON_RIGHT_ICON));
      next.setAttribute("aria-label", "Next slide");
      next.addEventListener("click", () => {
        this.next();
      });
      this.fullscreenButton = document.createElement("button");
      this.fullscreenButton.type = "button";
      this.fullscreenButton.className = "toolbar-button";
      this.fullscreenButton.addEventListener("click", () => {
        void this.toggleFullscreen();
      });
      toolbar.append(
        previous,
        this.counter,
        next,
        this.fullscreenButton
      );
      return toolbar;
    }
    handleFullscreenChange = () => {
      this.updateFullscreenButton();
    };
    updateFullscreenButton() {
      if (!this.fullscreenButton) {
        return;
      }
      const isFullscreen = document.fullscreenElement === this.container;
      this.fullscreenButton.replaceChildren(
        createLucideIcon(
          isFullscreen ? MINIMIZE_ICON : MAXIMIZE_ICON
        )
      );
      this.fullscreenButton.setAttribute("aria-label", isFullscreen ? "Exit fullscreen" : "Enter fullscreen");
      this.fullscreenButton.setAttribute("aria-pressed", String(isFullscreen));
    }
    bindKeyboard(root) {
      root.addEventListener("keydown", (event) => {
        switch (event.key) {
          case "ArrowRight":
          case "PageDown":
          case " ":
            event.preventDefault();
            this.next();
            break;
          case "ArrowLeft":
          case "PageUp":
            event.preventDefault();
            this.previous();
            break;
          case "Home":
            event.preventDefault();
            this.show(0);
            break;
          case "End":
            event.preventDefault();
            this.show(
              this.data.slides.length - 1
            );
            break;
        }
      });
      root.addEventListener("pointerdown", () => {
        root.focus({ preventScroll: true });
      });
    }
    playAnimation(animationName) {
      if (!animationName || !/^[a-zA-Z_][\w-]*$/.test(animationName)) {
        return Promise.resolve();
      }
      const duration = Math.max(0, this.data.transition.duration);
      if (this.activeAnimationClass) {
        this.viewport.classList.remove(
          this.activeAnimationClass
        );
      }
      this.viewport.classList.remove("fx-animated");
      this.viewport.style.setProperty("--fx-dur", `${duration}ms`);
      void this.viewport.offsetWidth;
      this.activeAnimationClass = animationName;
      this.viewport.classList.add("fx-animated", animationName);
      return new Promise((resolve) => {
        let timeoutId = 0;
        let finished = false;
        const finish = () => {
          if (finished) {
            return;
          }
          finished = true;
          window.clearTimeout(timeoutId);
          this.viewport.removeEventListener("animationend", handleAnimationEnd);
          this.viewport.classList.remove("fx-animated", animationName);
          if (this.activeAnimationClass === animationName) {
            this.activeAnimationClass = null;
          }
          resolve();
        };
        const handleAnimationEnd = (event) => {
          if (event.target === this.viewport) {
            finish();
          }
        };
        this.viewport.addEventListener("animationend", handleAnimationEnd);
        timeoutId = window.setTimeout(finish, duration + 100);
      });
    }
    async showWithAnimation(index) {
      if (this.isAnimating) {
        return;
      }
      this.isAnimating = true;
      try {
        const animation = this.data.transition.animation;
        await this.playAnimation(animation?.hide);
        this.currentIndex = index;
        this.applySlide(this.data.slides[this.currentIndex], false);
        this.updateCounter();
        await this.playAnimation(animation?.show);
      } finally {
        this.isAnimating = false;
      }
    }
    updateCounter() {
      this.counter.textContent = `${this.currentIndex + 1} / ${this.data.slides.length}`;
    }
    applySlide(slide, animate) {
      if (!slide) {
        return;
      }
      const viewportWidth = this.viewport.clientWidth;
      const viewportHeight = this.viewport.clientHeight;
      if (viewportWidth <= 0 || viewportHeight <= 0) {
        return;
      }
      const scale = Math.min(
        viewportWidth / slide.width,
        viewportHeight / slide.height
      );
      const renderedWidth = slide.width * scale;
      const renderedHeight = slide.height * scale;
      const centerX = (viewportWidth - renderedWidth) / 2;
      const centerY = (viewportHeight - renderedHeight) / 2;
      if (this.data.clip === true) {
        const top = Math.max(0, centerY);
        const left = Math.max(0, centerX);
        const right = Math.max(0, viewportWidth - centerX - renderedWidth);
        const bottom = Math.max(0, viewportHeight - centerY - renderedHeight);
        this.viewport.style.clipPath = `inset(${top}px ${right}px ${bottom}px ${left}px)`;
      } else {
        this.viewport.style.clipPath = "none";
      }
      const translateX = centerX - slide.x * scale;
      const translateY = centerY - slide.y * scale;
      const transition = this.data.transition;
      this.sceneLayer.style.transition = animate && transition.type === "pan" ? `transform ${transition.duration}ms ${transition.easing}` : "none";
      this.sceneLayer.style.transform = `matrix(${scale}, 0, 0, ${scale}, ${translateX}, ${translateY})`;
    }
    show(index, animate = true) {
      if (this.data.slides.length === 0) {
        return;
      }
      const nextIndex = Math.max(
        0,
        Math.min(
          index,
          this.data.slides.length - 1
        )
      );
      if (animate && nextIndex === this.currentIndex) {
        return;
      }
      if (animate && this.data.transition.type === "animation") {
        void this.showWithAnimation(nextIndex);
        return;
      }
      this.currentIndex = nextIndex;
      this.applySlide(this.data.slides[this.currentIndex], animate);
      this.updateCounter();
    }
    next() {
      const slidesCount = this.data.slides.length;
      if (slidesCount <= 1) {
        return;
      }
      const isLast = this.currentIndex === slidesCount - 1;
      if (isLast) {
        if (this.data.loop === true) {
          this.show(0);
        }
        return;
      }
      this.show(this.currentIndex + 1);
    }
    previous() {
      const slidesCount = this.data.slides.length;
      if (slidesCount <= 1) {
        return;
      }
      const isFirst = this.currentIndex === 0;
      if (isFirst) {
        if (this.data.loop === true) {
          this.show(slidesCount - 1);
        }
        return;
      }
      this.show(this.currentIndex - 1);
    }
    async toggleFullscreen() {
      if (document.fullscreenElement === this.container) {
        await document.exitFullscreen();
        return;
      }
      await this.container.requestFullscreen();
    }
    destroy() {
      document.removeEventListener("fullscreenchange", this.handleFullscreenChange);
      this.resizeObserver.disconnect();
      this.shadowRoot.replaceChildren();
    }
  }

  const styles = ".fx-animated {\n  animation-duration: var(--fx-dur, 2000ms);\n  animation-timing-function: ease-in-out;\n  animation-delay: var(--fx-delay, 0ms);\n}\n\n@keyframes fx-backInDown {\n  0% {\n    opacity: 0.7;\n    transform: translateY(-1200px) scale(0.7);\n  }\n  80% {\n    opacity: 0.7;\n    transform: translateY(0) scale(0.7);\n  }\n  100% {\n    opacity: 1;\n    transform: scale(1);\n  }\n}\n.fx-backInDown {\n  animation-name: fx-backInDown;\n}\n\n@keyframes fx-backInLeft {\n  0% {\n    opacity: 0.7;\n    transform: translateX(-2000px) scale(0.7);\n  }\n  80% {\n    opacity: 0.7;\n    transform: translateX(0) scale(0.7);\n  }\n  100% {\n    opacity: 1;\n    transform: scale(1);\n  }\n}\n.fx-backInLeft {\n  animation-name: fx-backInLeft;\n}\n\n@keyframes fx-backInRight {\n  0% {\n    opacity: 0.7;\n    transform: translateX(2000px) scale(0.7);\n  }\n  80% {\n    opacity: 0.7;\n    transform: translateX(0) scale(0.7);\n  }\n  100% {\n    opacity: 1;\n    transform: scale(1);\n  }\n}\n.fx-backInRight {\n  animation-name: fx-backInRight;\n}\n\n@keyframes fx-backInUp {\n  0% {\n    opacity: 0.7;\n    transform: translateY(1200px) scale(0.7);\n  }\n  80% {\n    opacity: 0.7;\n    transform: translateY(0) scale(0.7);\n  }\n  100% {\n    opacity: 1;\n    transform: scale(1);\n  }\n}\n.fx-backInUp {\n  animation-name: fx-backInUp;\n}\n\n@keyframes fx-backOutDown {\n  0% {\n    opacity: 1;\n    transform: scale(1);\n  }\n  20% {\n    opacity: 0.7;\n    transform: translateY(0) scale(0.7);\n  }\n  100% {\n    opacity: 0.7;\n    transform: translateY(700px) scale(0.7);\n  }\n}\n.fx-backOutDown {\n  animation-name: fx-backOutDown;\n}\n\n@keyframes fx-backOutLeft {\n  0% {\n    opacity: 1;\n    transform: scale(1);\n  }\n  20% {\n    opacity: 0.7;\n    transform: translateX(0) scale(0.7);\n  }\n  100% {\n    opacity: 0.7;\n    transform: translateX(-2000px) scale(0.7);\n  }\n}\n.fx-backOutLeft {\n  animation-name: fx-backOutLeft;\n}\n\n@keyframes fx-backOutRight {\n  0% {\n    opacity: 1;\n    transform: scale(1);\n  }\n  20% {\n    opacity: 0.7;\n    transform: translateX(0) scale(0.7);\n  }\n  100% {\n    opacity: 0.7;\n    transform: translateX(2000px) scale(0.7);\n  }\n}\n.fx-backOutRight {\n  animation-name: fx-backOutRight;\n}\n\n@keyframes fx-backOutUp {\n  0% {\n    opacity: 1;\n    transform: scale(1);\n  }\n  20% {\n    opacity: 0.7;\n    transform: translateY(0) scale(0.7);\n  }\n  100% {\n    opacity: 0.7;\n    transform: translateY(-700px) scale(0.7);\n  }\n}\n.fx-backOutUp {\n  animation-name: fx-backOutUp;\n}\n\n:host {\n  --presentation-background-color: #fff;\n  display: block;\n  box-sizing: border-box;\n  width: 100%;\n  height: 400px;\n  background: var(--presentation-background-color);\n}\n\n* {\n  box-sizing: border-box;\n}\n\n.presentation {\n  --padding-top: 24px;\n  --padding-right: 24px;\n  --padding-bottom: 24px;\n  --padding-left: 24px;\n  position: relative;\n  width: 100%;\n  height: 100%;\n  padding: var(--padding-top) var(--padding-right) var(--padding-bottom) var(--padding-left);\n  background: var(--presentation-background-color);\n  overflow: hidden;\n  outline: none;\n}\n\n.viewport {\n  position: relative;\n  width: 100%;\n  height: 100%;\n  will-change: transform, clip-path;\n}\n\n.scene-layer {\n  position: absolute;\n  top: 0;\n  left: 0;\n  transform-origin: 0 0;\n  will-change: transform;\n}\n\n.scene-layer > svg {\n  display: block;\n  width: 100%;\n  height: 100%;\n}\n\n.toolbar {\n  position: absolute;\n  left: 50%;\n  bottom: 12px;\n  z-index: 10;\n  display: flex;\n  align-items: center;\n  gap: 6px;\n  padding: 6px;\n  transform: translateX(-50%);\n  border: 1px solid rgba(0, 0, 0, 0.12);\n  border-radius: 10px;\n  background: rgba(255, 255, 255, 0.92);\n  box-shadow: 0 4px 18px rgba(0, 0, 0, 0.15);\n  backdrop-filter: blur(8px);\n}\n\n.toolbar-button {\n  display: inline-flex;\n  width: 32px;\n  height: 32px;\n  align-items: center;\n  justify-content: center;\n  border: 0;\n  border-radius: 6px;\n  color: #222;\n  background: transparent;\n  cursor: pointer;\n}\n.toolbar-button:hover {\n  background: rgba(0, 0, 0, 0.08);\n}\n.toolbar-button:focus-visible {\n  outline: 2px solid #6965db;\n  outline-offset: 1px;\n}\n\n.toolbar-icon {\n  display: block;\n  width: 18px;\n  height: 18px;\n  pointer-events: none;\n}\n\n.counter {\n  min-width: 54px;\n  text-align: center;\n  font: 500 12px/1.2 system-ui, sans-serif;\n  color: #555;\n}\n\n:host(:fullscreen) {\n  width: 100vw !important;\n  height: 100vh !important;\n  background: var(--presentation-background-color);\n}\n:host(:fullscreen) .presentation {\n  width: 100%;\n  height: 100%;\n}\n:host(:fullscreen) .viewport {\n  width: 100%;\n  height: 100%;\n}";

  const instances = /* @__PURE__ */ new WeakMap();
  const initialize = async (container) => {
    if (instances.has(container)) {
      return;
    }
    const id = container.dataset.id;
    if (!id) {
      console.error("NinjaDraw presentation ID is missing");
      return;
    }
    const endpoint = `${g.api.presentation_url.replace(/\/$/, "")}/${encodeURIComponent(id)}`;
    const response = await fetch(endpoint);
    if (!response.ok) {
      throw new Error(
        `Presentation request failed: ${response.status}`
      );
    }
    const json = await response.json();
    if (!json.success || !json.data?.svg || !json.data.presentation) {
      throw new Error(
        "Invalid NinjaDraw presentation response"
      );
    }
    const player = new PresentationPlayer(
      container,
      {
        svg: json.data.svg,
        presentation: json.data.presentation,
        styles
      }
    );
    instances.set(container, player);
  };
  const init = async () => {
    const containers = document.querySelectorAll(
      ".ninjadraw-presentation"
    );
    await Promise.all(
      Array.from(containers).map(async (container) => {
        try {
          await initialize(container);
        } catch (error) {
          console.error(
            "NinjaDraw presentation initialization failed",
            error
          );
        }
      })
    );
  };
  const Presentation = {
    init,
    initialize
  };
  window.Yalogica ??= {};
  window.Yalogica.NinjaDraw ??= {};
  window.Yalogica.NinjaDraw.Presentation = Presentation;
  window.addEventListener("DOMContentLoaded", () => {
    void init();
  });

  exports.init = init;
  exports.initialize = initialize;

  Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });

  return exports;

})({});
