/**
 * @file
 * javascript for the tao_iching module.
 *
 */
(function ($, Drupal) {
  Drupal.behaviors.tao_iching = {
    attach: function (context, settings) {
      const ichingForm = document.getElementById('tao-iching-form');
      if(ichingForm) {
        Drupal.Ajax.prototype.beforeSubmit = function (form_values, element, options) {
          const coin = document.getElementById('coin');
          coin.innerHTML = '<img class="heads animate-coin" src="/modules/custom/tao_iching/imgs/heads.png"/><img class="heads animate-coin" src="/modules/custom/tao_iching/imgs/heads.png"/><img class="heads animate-coin" src="/modules/custom/tao_iching/imgs/heads.png"/>';
        }
      }
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
}(jQuery, Drupal));
