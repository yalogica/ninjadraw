(function (blocks, element, blockEditor, components, i18n, apiFetch, serverSideRender) {
  'use strict';

  var el = element.createElement;
  var useState = element.useState;
  var useEffect = element.useEffect;
  var useCallback = element.useCallback;
  var useMemo = element.useMemo;
  var __ = i18n.__;
  var InspectorControls = blockEditor.InspectorControls;
  var BlockControls = blockEditor.BlockControls;
  var useBlockProps = blockEditor.useBlockProps;
  var PanelBody = components.PanelBody;
  var SelectControl = components.SelectControl;
  var ToggleControl = components.ToggleControl;
  var ToolbarGroup = components.ToolbarGroup;
  var ToolbarButton = components.ToolbarButton;
  var Placeholder = components.Placeholder;
  var Spinner = components.Spinner;
  var Notice = components.Notice;
  var config = window.yalogica_ninjadraw_image_block;

  function hydrate(node) {
    if (!node || !node.dataset.id || node.dataset.process) return;
    node.dataset.process = '1';

    var imageNode = node.querySelector('.ninjadraw-image');
    if (!imageNode) return;

    fetch(config.publicUrl + '/images/' + node.dataset.id)
      .then(function (response) { return response.json(); })
      .then(function (response) {
        if (response && response.success) {
          imageNode.innerHTML = response.image;
          node.classList.add('ninjadraw-ready');
        }
        delete node.dataset.process;
      })
      .catch(function () {
        delete node.dataset.process;
      });
  }

  function ItemSelect(props) {
    return el(SelectControl, {
      label: __('Diagram', 'ninjadraw'),
      value: props.value ? String(props.value) : '0',
      options: [{ value: '0', label: __('Select a diagram', 'ninjadraw') }].concat(
        props.options.map(function (option) {
          return { value: String(option.id), label: option.title };
        })
      ),
      onChange: function (next) { props.onChange(next || ''); }
    });
  }

  function Edit(props) {
    var attributes = props.attributes;
    var setAttributes = props.setAttributes;
    var blockProps = useBlockProps();
    var itemId = attributes.itemId || 0;

    var state = useState({ options: null, error: '' });
    var data = state[0];
    var setData = state[1];

    useEffect(function () {
      apiFetch({ url: config.optionsUrl })
        .then(function (response) {
          setData({ options: (response.data && response.data.options) || [], error: '' });
        })
        .catch(function () {
          setData({
            options: [],
            error: __('Failed to load diagrams. Please check your permissions.', 'ninjadraw')
          });
        });
    }, []);

    var setPreviewRef = useCallback(function (node) {
      if (!node) return;

      var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
          Array.prototype.forEach.call(mutation.addedNodes, function (added) {
            if (added.nodeType !== 1) return;
            if (added.classList && added.classList.contains('ninjadraw')) {
              hydrate(added);
            }
            if (added.querySelectorAll) {
              Array.prototype.forEach.call(added.querySelectorAll('.ninjadraw'), hydrate);
            }
          });
        });
      });

      observer.observe(node, { childList: true, subtree: true });
      Array.prototype.forEach.call(node.querySelectorAll('.ninjadraw'), hydrate);
    }, []);

    var preview = useMemo(function () {
      return el(serverSideRender, {
        block: 'ninjadraw/image-block',
        attributes: attributes
      });
    }, [attributes]);

    if (data.options === null) {
      return el(Placeholder, { icon: 'format-image', label: 'NinjaDraw' }, el(Spinner, null));
    }

    if (data.error) {
      return el(Placeholder, { icon: 'format-image', label: 'NinjaDraw' },
        el(Notice, { status: 'error', isDismissible: false }, data.error)
      );
    }

    if (!itemId) {
      return el(Placeholder, { icon: 'format-image', label: 'NinjaDraw ' + __('Diagram', 'ninjadraw') },
        data.options.length
          ? el(ItemSelect, {
              options: data.options,
              value: '',
              onChange: function (id) { setAttributes({ itemId: id }); }
            })
          : __('No published diagrams found. Create and publish a diagram first.', 'ninjadraw')
      );
    }
    
    return el('div', blockProps,
      config.canEdit
        ? el(BlockControls, null,
            el(ToolbarGroup, null,
              el(ToolbarButton, {
                icon: 'edit',
                label: __('Edit diagram', 'ninjadraw'),
                onClick: function () {
                  window.open(config.editUrl + '&id=' + itemId, '_blank', 'noopener');
                }
              })
            )
          )
        : null,
      el(InspectorControls, null,
        el(PanelBody, { title: __('NinjaDraw Settings', 'ninjadraw'), initialOpen: true },
          el(ItemSelect, {
            options: data.options,
            value: itemId,
            onChange: function (id) { setAttributes({ itemId: id || '' }); }
          }),
          el(ToggleControl, {
            label: __('Show Title', 'ninjadraw'),
            checked: !!attributes.showTitle,
            onChange: function (value) { setAttributes({ showTitle: value }); }
          })
        )
      ),
      el('div', { ref: setPreviewRef }, preview)
    );
  }

  var icon = el('svg',
    { viewBox: '0 0 24 24', xmlns: 'http://www.w3.org/2000/svg', width: 24, height: 24 },
    el('path', { d: 'm18.447 1.1565c-0.44023-0.43911-1.0117-0.45733-1.4701-2e-7l-16.171 16.15v5.8728h5.8803l16.171-16.15c0.45849-0.45839 0.44106-1.0288 1e-6 -1.4682zm-0.73504 2.2023 2.9401 2.9364-1.4701 1.4682-2.9401-2.9364zm-2.9401 2.9364 2.9401 2.9364-10.29 10.277-2.9401-2.9364zm-11.761 11.746 2.9401 2.9364h-2.9401zm3.3697-17.22c-0.25977 0.0022195-0.51626 0.11922-0.74023 0.34375l-4.4922 4.4902c-0.44764 0.44821-0.46622 1.0294 0 1.4961l4.1621 4.1641 1.5-1.498c-0.2994-0.30021-0.60002-0.59919-0.90039-0.89844l1.5039-1.5039-1.4141-1.4141-1.5039 1.5039c-0.36896-0.36607-0.736-0.73405-1.1035-1.1016l2.9961-2.9941 3.4141 3.416 1.4941-1.4961-4.1602-4.1641c-0.23364-0.23341-0.49609-0.34597-0.75586-0.34375zm12.291 11.881-1.4961 1.4961 3.4297 3.4316-2.9922 2.9922c-0.37391-0.37088-0.74533-0.74425-1.1172-1.1172l1.5039-1.5039-1.4141-1.4141-1.5039 1.5039c-0.30109-0.29787-0.59938-0.59853-0.89844-0.89844l-1.4961 1.498 4.1758 4.1777c0.47328 0.473 1.118 0.37827 1.5723-0.07617l4.4141-4.4141c0.44853-0.44783 0.46709-1.0298 0-1.4961z' })
  );

  blocks.registerBlockType('ninjadraw/image-block', {
    icon: icon,
    edit: Edit,
    save: function () {
      return null; // dynamic block, render: php
    }
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.blockEditor,
  window.wp.components,
  window.wp.i18n,
  window.wp.apiFetch,
  window.wp.serverSideRender
);