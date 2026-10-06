(function (wp) {
  'use strict';
  var el = wp.element.createElement;
  var __ = wp.i18n.__;
  wp.blocks.registerBlockType('robse-one/contact', {
    edit: function (props) {
      function field(label, key, type) {
        return el('label', { className: 'robse-contact-field' }, el('span', null, label), el('input', {
          type: type || 'text', value: props.attributes[key] || '',
          onChange: function (event) { var next = {}; next[key] = event.target.value; props.setAttributes(next); }
        }));
      }
      return el('div', wp.blockEditor.useBlockProps({ className: 'robse-contact-editor' }),
        el('h2', null, props.attributes.heading || __('Contact', 'robse-one-blocks')),
        field(__('Heading', 'robse-one-blocks'), 'heading'),
        field(__('Phone number', 'robse-one-blocks'), 'phone', 'tel'),
        field(__('Email address', 'robse-one-blocks'), 'email', 'email'),
        el('p', null, __('Enter your real contact details. Empty or invalid links are hidden on the website. Set the HTML anchor to contact if buttons point to #contact.', 'robse-one-blocks')),
        el('p', null, __('The phone link opens a calling app. The email link opens a mail app; it does not send a message automatically.', 'robse-one-blocks'))
      );
    },
    save: function () { return null; }
  });
}(window.wp));
