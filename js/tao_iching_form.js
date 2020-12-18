/**
 * coin flip effect
 */
 Backdrop.ajax.prototype.beforeSubmit = function (form_values, element, options)
 {
   var chk = document.getElementById('tao-iching-reading');
   if (chk) {
   coin.innerHTML = '<img class="heads animate-coin" src="/modules/tao_iching/imgs/heads.png"/><img class="heads animate-coin" src="/modules/tao_iching/imgs/heads.png"/><img class="heads animate-coin" src="/modules/tao_iching/imgs/heads.png"/>';
   }
 };