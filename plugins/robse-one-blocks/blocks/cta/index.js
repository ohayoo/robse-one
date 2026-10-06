(function (wp) {
  'use strict';

  var el = wp.element.createElement;
  var __ = wp.i18n.__;

  // Used when the attribute has never been set. render.php uses the same texts.
  var defaults = {
    heading: __('Feel free to contact us', 'robse-one-blocks'),
    body: __('We look forward to your questions and quote requests about our services.', 'robse-one-blocks'),
    buttonLabel: __('Contact us', 'robse-one-blocks')
  };

  function value(attributes, key) {
    if (typeof attributes[key] === 'string') {
      return attributes[key];
    }
    return defaults[key] || '';
  }

  wp.blocks.registerBlockType('robse-one/cta', {
    edit: function (props) {
      var attributes = props.attributes;

      function field(label, key, multiline) {
        return el('label', { className: 'robse-cta-editor__field' },
          el('span', null, label),
          el(multiline ? 'textarea' : 'input', {
            type: multiline ? undefined : 'text',
            rows: multiline ? 3 : undefined,
            value: value(attributes, key),
            onChange: function (event) {
              var next = {};
              next[key] = event.target.value;
              props.setAttributes(next);
            }
          })
        );
      }

      return el('div', wp.blockEditor.useBlockProps({ className: 'robse-cta-editor' }),
        el('div', { className: 'robse-cta-editor__preview' },
          el('h2', null, value(attributes, 'heading') || __('Enter a heading', 'robse-one-blocks')),
          el('p', null, value(attributes, 'body') || __('Enter a description', 'robse-one-blocks')),
          attributes.buttonUrl ? el('span', { className: 'robse-block-cta__button' }, value(attributes, 'buttonLabel') || __('Button text', 'robse-one-blocks')) : el('p', null, __('Add a link URL to show the contact button.', 'robse-one-blocks'))
        ),
        el('div', { className: 'robse-cta-editor__fields' },
          field(__('Heading', 'robse-one-blocks'), 'heading', false),
          field(__('Description', 'robse-one-blocks'), 'body', true),
          field(__('Button text', 'robse-one-blocks'), 'buttonLabel', false),
          field(__('Link URL', 'robse-one-blocks'), 'buttonUrl', false)
        )
      );
    },
    save: function () { return null; }
  });
}(window.wp));
