/**
 * @file
 * javascript for the tao_iching module.
 *
 */
(function (Drupal, once) {
   'use strict';
  Drupal.behaviors.tao_iching = {
    attach: function (context, settings) {
      const ichingForm = once('tao-iching-form', 'form[data-drupal-selector="tao-iching-form"]', context);
      ichingForm.forEach((form) => {
        // Change the text of the submit button when we have clicked 6 times.
        var submitButton = form.querySelector('#edit-submit-button');
        once.remove('sync-reload-values', '#throw_num', document);

        submitButton.addEventListener('click', function () {          
          if (form.querySelector('#throw_num').innerHTML.trim() !== '') {
            if (form.querySelector('#throw_num').textContent === '6') {
              submitButton.setAttribute('value', "Get The Result");
            }
          }
        });

      });
    }
  };

  Drupal.behaviors.tao_iching_tabs = {
    attach: function (context, settings)  {
      const tablinks = context.getElementsByClassName('tablinks');
      const tabcontent = context.getElementsByClassName('tabcontent');
      if (tabcontent.length !== 0) {
        // set initial display
        for (let x = 0; x < tabcontent.length; x++) {
          if (tabcontent[x].id === 'current') {
            tabcontent[x].style.display = "block";
          }
          else {
            tabcontent[x].style.display = "none";
          }
        }
        for (let z = 0; z < tablinks.length; z++) {
          tablinks[z].addEventListener('click', function(i){
            let sectionName = i.target.firstChild.data.toLowerCase();
            for (let x = 0; x < tabcontent.length; x++) {
              if (tabcontent[x].id === sectionName) {
                tabcontent[x].style.display = "block";
                for (let n = 0; n < tablinks.length; n++) {
                  tablinks[n].className = tablinks[n].className.replace(" active", "");
                }
                i.target.className += " active";
              }
              else {
                tabcontent[x].style.display = "none";
              }
            }
          }, false);
        }
      }
    }
  };
}(Drupal, once));
