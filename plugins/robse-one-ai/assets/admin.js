(function () {
  'use strict';
  var titleInput = document.getElementById('robse-one-ai-title');
  if (!titleInput) return;

  var brief = document.getElementById('robse-one-ai-brief');
  var consent = document.getElementById('robse-one-ai-consent');
  var preview = document.getElementById('robse-one-ai-preview');
  var sectionsRoot = document.getElementById('robse-one-ai-sections');
  var status = document.getElementById('robse-one-ai-status');
  var generated = null;

  function setStatus(message, error) {
    status.textContent = message || '';
    status.className = error ? 'robse-ai-error' : 'robse-ai-message';
  }

  function api(path, data) {
    return fetch(robseOneAi.apiUrl + path, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': robseOneAi.nonce
      },
      body: JSON.stringify(data)
    }).then(function (response) {
      return response.json().then(function (payload) {
        if (!response.ok) {
          throw new Error(payload.message || robseOneAi.texts.failed);
        }
        return payload;
      });
    });
  }

  document.getElementById('robse-one-ai-generate').addEventListener('click', function () {
    if (!consent.checked) {
      setStatus('外部AIへの送信に同意してください。', true);
      consent.focus();
      return;
    }
    if (brief.value.trim().length < 10) {
      setStatus('依頼内容を10文字以上入力してください。', true);
      brief.focus();
      return;
    }
    generated = null;
    preview.hidden = true;
    setStatus(robseOneAi.texts.working, false);
    api('generate', { brief: brief.value, consent: true }).then(function (result) {
      generated = result;
      titleInput.value = result.title;
      sectionsRoot.replaceChildren();
      result.sections.forEach(function (section, index) {
        var fieldset = document.createElement('fieldset');
        fieldset.className = 'robse-ai-section-field';
        var legend = document.createElement('legend');
        legend.textContent = 'セクション ' + (index + 1);
        var headingLabel = document.createElement('label');
        headingLabel.textContent = '見出し';
        var heading = document.createElement('input');
        heading.type = 'text';
        heading.className = 'robse-ai-heading';
        heading.maxLength = 120;
        heading.value = section.heading;
        headingLabel.appendChild(heading);
        var bodyLabel = document.createElement('label');
        bodyLabel.textContent = '本文';
        var body = document.createElement('textarea');
        body.className = 'robse-ai-body';
        body.rows = 3;
        body.maxLength = 1000;
        body.value = section.body;
        bodyLabel.appendChild(body);
        fieldset.append(legend, headingLabel, bodyLabel);
        sectionsRoot.appendChild(fieldset);
      });
      preview.hidden = false;
      setStatus('', false);
      titleInput.focus();
    }).catch(function (error) {
      setStatus(error.message, true);
    });
  });

  document.getElementById('robse-one-ai-create').addEventListener('click', function () {
    if (!generated) return;
    var sections = [];
    sectionsRoot.querySelectorAll('.robse-ai-section-field').forEach(function (fieldset) {
      sections.push({
        heading: fieldset.querySelector('.robse-ai-heading').value,
        body: fieldset.querySelector('.robse-ai-body').value
      });
    });
    setStatus(robseOneAi.texts.creating, false);
    api('draft', { title: titleInput.value, sections: sections }).then(function (result) {
      setStatus(robseOneAi.texts.success, false);
      var link = document.createElement('a');
      link.href = result.edit_url;
      link.textContent = '下書きを編集する';
      link.className = 'button';
      link.style.marginLeft = '8px';
      status.appendChild(link);
    }).catch(function (error) {
      setStatus(error.message, true);
    });
  });
}());
