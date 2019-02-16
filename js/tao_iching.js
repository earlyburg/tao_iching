function openBook(evt, bookName) {
	// Declare all variables
    	var i, iching_tabcontent, iching_tablinks;

    	// Get all elements with class="tabcontent" and hide them
    	iching_tabcontent = document.getElementsByClassName("iching_tabcontent");
    	for (i = 0; i < iching_tabcontent.length; i++) {
        	iching_tabcontent[i].style.display = "none";
    	}

    	// Get all elements with class="tablinks" and remove the class "active"
    	iching_tablinks = document.getElementsByClassName("iching_tablinks");
    	for (i = 0; i < iching_tablinks.length; i++) {
        	iching_tablinks[i].className = iching_tablinks[i].className.replace(" active", "");
    	}

    	// Show the current tab, and add an "active" class to the button that opened the tab
    	document.getElementById(bookName).style.display = "inline-block";
    	evt.currentTarget.className += " active";
}


jQuery(document).ready(function($)
{

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

});

