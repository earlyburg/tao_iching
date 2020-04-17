/**
 * Tab effects for the /result page
 */
function openBook(evt, bookName) {
	/* Declare all variables */
    	var i, iching_tabcontent, iching_tablinks;

    	/* Get all elements with class="tabcontent" and hide them */
    	iching_tabcontent = document.getElementsByClassName("iching_tabcontent");
    	for (i = 0; i < iching_tabcontent.length; i++) {
        	iching_tabcontent[i].style.display = "none";
    	}

    	/* Get all elements with class="tablinks" and remove the class "active" */
    	iching_tablinks = document.getElementsByClassName("iching_tablinks");
    	for (i = 0; i < iching_tablinks.length; i++) {
        	iching_tablinks[i].className = iching_tablinks[i].className.replace(" active", "");
    	}

    	/* Show the current tab, and add an "active" class to the button that opened the tab */
    	document.getElementById(bookName).style.display = "inline-block";
    	evt.currentTarget.className += " active";
}


/**
 * Credit container slider effect
 */
jQuery(document).ready(function($) {

	$("#creditcontainer_toggle").click(function()
	{
    		$("#creditcontainer").slideToggle( "slow");

      		if ($("#creditcontainer_toggle").text() == "Credits")
      		{
        		$("#creditcontainer_toggle").html("Hide Credits")
      		}
      		else
      		{
        		$("#creditcontainer_toggle").text("Credits")
      		}
  	});

	/* Set variables for the chart display */
	var PIECHART = document.getElementById('piechart');
 	if (PIECHART) {
  		var data = [{
   		values: valArray,
    		labels: labelArray,
    		type: 'pie',
    		hoverinfo: 'label',
    		textinfo: 'label+percent'
  		}];

  		var layout = {
    		height: 400,
    		width: 600,
    		showlegend: false,
  		};

  		Plotly.newPlot('piechart', data, layout, {responsive: true});
	}
});


/**
 * Modify form values prior to form submission.
 */
jQuery(document).ready(function($) {
	var coin = document.getElementById('coin');
});


Drupal.ajax.prototype.beforeSubmit = function (form_values, element, options)
{
	var chk = document.getElementById('tao-iching-form');
   	if (chk) {
	 	coin.innerHTML = '<img class="heads animate-coin" src="/sites/all/modules/tao_iching/imgs/heads.png"/><img class="heads animate-coin" src="/sites/all/modules/tao_iching/imgs/heads.png"/><img class="heads animate-coin" src="/sites/all/modules/tao_iching/imgs/heads.png"/>';
   	}

};

