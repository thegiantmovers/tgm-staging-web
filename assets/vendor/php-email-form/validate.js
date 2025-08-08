/**
* PHP Email Form Validation - v3.10
* URL: https://bootstrapmade.com/php-email-form/
* Author: BootstrapMade.com
* Note: This file has been modified to support Google reCAPTCHA Enterprise and server-side redirects.
*/
(function () {
  "use strict";

  let forms = document.querySelectorAll('.php-email-form');

  forms.forEach( function(e) {
    e.addEventListener('submit', function(event) {
      event.preventDefault();

      let thisForm = this;

      let action = thisForm.getAttribute('action');
      let recaptcha = thisForm.getAttribute('data-recaptcha-site-key');
      
      if( ! action ) {
        displayError(thisForm, 'The form action property is not set!');
        return;
      }
      thisForm.querySelector('.loading').classList.add('d-block');
      thisForm.querySelector('.error-message').classList.remove('d-block');
      thisForm.querySelector('.sent-message').classList.remove('d-block');

      let formData = new FormData( thisForm );

      if ( recaptcha ) {
        // CORRECTED: Use grecaptcha.enterprise.ready() for the Enterprise API
        // Check for the existence of grecaptcha.enterprise to avoid an error if the script isn't loaded
        if(typeof grecaptcha.enterprise !== "undefined" ) { 
          grecaptcha.enterprise.ready(async function() {
            try {
              const token = await grecaptcha.enterprise.execute(recaptcha, {action: 'php_email_form_submit'});
              formData.set('recaptcha-response', token);
              php_email_form_submit(thisForm, action, formData);
            } catch(error) {
              displayError(thisForm, error);
            }
          });
        } else {
          displayError(thisForm, 'The reCAPTCHA Enterprise JavaScript API url is not loaded!');
        }
      } else {
        php_email_form_submit(thisForm, action, formData);
      }
    });
  });

  function php_email_form_submit(thisForm, action, formData) {
    fetch(action, {
      method: 'POST',
      body: formData,
      headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(response => {
      // Check for a server-side redirect (which is a successful submission)
      if( response.ok && response.redirected ) {
        window.location.href = response.url;
        return; // Exit the promise chain
      }
      // If no redirect, assume an error or an unexpected response
      if( response.ok ) {
        return response.text();
      } else {
        // Handle server errors (e.g., 400 Bad Request from PHP)
        throw new Error(`${response.status} ${response.statusText} ${response.url}`); 
      }
    })
    .then(data => {
      // This block is for non-redirecting successful responses (e.g., 'OK' text)
      thisForm.querySelector('.loading').classList.remove('d-block');
      if (data && data.trim() == 'OK') {
        thisForm.querySelector('.sent-message').classList.add('d-block');
        thisForm.reset(); 
      } else {
        throw new Error(data ? data : 'Form submission failed and no error message returned from: ' + action); 
      }
    })
    .catch((error) => {
      // This handles all types of errors (network, server-side, redirect failures)
      displayError(thisForm, error);
    });
  }

  function displayError(thisForm, error) {
    thisForm.querySelector('.loading').classList.remove('d-block');
    thisForm.querySelector('.error-message').innerHTML = error;
    thisForm.querySelector('.error-message').classList.add('d-block');
  }

})();
