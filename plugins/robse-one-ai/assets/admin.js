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
  var busy = false;
  var generateButton = document.getElementById('robse-one-ai-generate');
  var createButton = document.getElementById('robse-one-ai-create');
  function setBusy(value) {
    busy = value;
    generateButton.disabled = value;
    createButton.disabled = value || !generated;
    preview.setAttribute('aria-busy', value ? 'true' : 'false');
  }
  setBusy(false);

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

  generateButton.addEventListener('click', function () {
    if (busy) return;
    if (!consent.checked) {
      setStatus(robseOneAi.texts.consent, true);
      consent.focus();
      return;
    }
    if (brief.value.trim().length < 10) {
      setStatus(robseOneAi.texts.tooShort, true);
      brief.focus();
      return;
    }
    generated = null;
    setBusy(true);
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
        legend.textContent = robseOneAi.texts.section.replace('%d', index + 1);
        var headingLabel = document.createElement('label');
        headingLabel.textContent = robseOneAi.texts.heading;
        var heading = document.createElement('input');
        heading.type = 'text';
        heading.className = 'robse-ai-heading';
        heading.maxLength = 120;
        heading.value = section.heading;
        headingLabel.appendChild(heading);
        var bodyLabel = document.createElement('label');
        bodyLabel.textContent = robseOneAi.texts.body;
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
      setBusy(false);
    }).catch(function (error) {
      setStatus(error.message, true);
      setBusy(false);
    });
  });

  createButton.addEventListener('click', function () {
    if (!generated || busy) return;
    setBusy(true);
    var sections = [];
    sectionsRoot.querySelectorAll('.robse-ai-section-field').forEach(function (fieldset) {
      sections.push({
        heading: fieldset.querySelector('.robse-ai-heading').value,
        body: fieldset.querySelector('.robse-ai-body').value
      });
    });
    setStatus(robseOneAi.texts.creating, false);
    api('draft', { title: titleInput.value, sections: sections }).then(function (result) {
      generated = null;
      setBusy(false);
      setStatus(robseOneAi.texts.success, false);
      var link = document.createElement('a');
      link.href = result.edit_url;
      link.textContent = robseOneAi.texts.editDraft;
      link.className = 'button';
      link.style.marginLeft = '8px';
      status.appendChild(link);
    }).catch(function (error) {
      setStatus(error.message, true);
      setBusy(false);
    });
  });
}());
