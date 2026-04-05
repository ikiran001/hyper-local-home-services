/**
 * Client-side validation helpers (MVP — server still validates).
 */
(function () {
  'use strict';

  function digitsOnly(s) {
    return String(s || '').replace(/\D/g, '');
  }

  function validateIndianPhone(phone) {
    var d = digitsOnly(phone);
    return d.length === 10;
  }

  var bookForm = document.getElementById('booking-form');
  if (bookForm) {
    bookForm.addEventListener('submit', function (e) {
      var name = bookForm.querySelector('[name="name"]');
      var phone = bookForm.querySelector('[name="phone"]');
      var address = bookForm.querySelector('[name="address"]');
      var issue = bookForm.querySelector('[name="issue"]');

      var errs = [];
      if (!name || name.value.trim().length < 2) errs.push('Enter a valid name.');
      if (!phone || !validateIndianPhone(phone.value)) errs.push('Enter a valid 10-digit phone number.');
      if (!address || address.value.trim().length < 5) errs.push('Enter a complete address.');
      if (!issue || issue.value.trim().length < 10) errs.push('Describe the problem (at least 10 characters).');

      if (errs.length) {
        e.preventDefault();
        alert(errs.join('\n'));
      }
    });
  }

  var statusForm = document.getElementById('status-form');
  if (statusForm) {
    statusForm.addEventListener('submit', function (e) {
      var phone = statusForm.querySelector('[name="phone"]');
      if (!phone || !validateIndianPhone(phone.value)) {
        e.preventDefault();
        alert('Enter a valid 10-digit phone number.');
      }
    });
  }

  var techLogin = document.getElementById('tech-login-form');
  if (techLogin) {
    techLogin.addEventListener('submit', function (e) {
      var phone = techLogin.querySelector('[name="phone"]');
      if (!phone || !validateIndianPhone(phone.value)) {
        e.preventDefault();
        alert('Enter a valid 10-digit phone number.');
      }
    });
  }
})();
