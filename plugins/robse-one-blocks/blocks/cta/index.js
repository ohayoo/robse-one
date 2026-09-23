(function (wp) {
  'use strict';

  var el = wp.element.createElement;

  wp.blocks.registerBlockType('robse-one/cta', {
    edit: function (props) {
      var attributes = props.attributes;

      function field(label, key, multiline) {
        return el('label', { className: 'robse-cta-editor__field' },
          el('span', null, label),
          el(multiline ? 'textarea' : 'input', {
            type: multiline ? undefined : 'text',
            rows: multiline ? 3 : undefined,
            value: attributes[key] || '',
            onChange: function (event) {
              var next = {};
              next[key] = event.target.value;
              props.setAttributes(next);
            }
          })
        );
      }

      return el('div', { className: 'robse-cta-editor' },
        el('div', { className: 'robse-cta-editor__preview' },
          el('h2', null, attributes.heading || '見出しを入力'),
          el('p', null, attributes.body || '説明文を入力'),
          el('span', { className: 'robse-block-cta__button' }, attributes.buttonLabel || 'ボタン文言')
        ),
        el('div', { className: 'robse-cta-editor__fields' },
          field('見出し', 'heading', false),
          field('説明', 'body', true),
          field('ボタン文言', 'buttonLabel', false),
          field('リンクURL', 'buttonUrl', false)
        )
      );
    },
    save: function () { return null; }
  });
}(window.wp));
