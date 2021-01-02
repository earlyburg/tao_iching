/**
 * coin flip effect - modify form values prior to form submission.
 */
jQuery(document).ready(function($)
{
  var coin = document.getElementById('coin');
});
Backdrop.ajax.prototype.beforeSubmit = function (form_values, element, options)
{
 var chk = document.getElementById('tao-iching-reading');
 if (chk) {
 coin.innerHTML = '<img class="heads animate-coin" src="/modules/tao_iching/imgs/heads.png"/><img class="heads animate-coin" src="/modules/tao_iching/imgs/heads.png"/><img class="heads animate-coin" src="/modules/tao_iching/imgs/heads.png"/>';
 }
};