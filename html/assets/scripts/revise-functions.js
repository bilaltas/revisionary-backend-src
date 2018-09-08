// FUNCTION: Initiate the inspector
function runTheInspector() {

	// WHEN IFRAME HAS LOADED
	$('iframe').on('load', function(){


		// Iframe element
	    iframe = $('iframe').contents();



		// CURSOR WORKS
		// Close Pin Mode pinTypeSelector - If on revise mode !!!
		toggleCursorActive(false, true);

		// Update the cursor number with the existing pins
		changePinNumber(currentPinNumber);



		// APPY THE MODIFICATIONS
		$(modifications).each(function(i, modification) {

			console.log(i, modification);


			// Find the element
			var element = iframe.find('[data-revisionary-index='+modification.element_index+']');


			// Add edited status
			element.attr('data-revisionary-edited', "0");


			// If the type is HTML content change
			if ( modification.modification_type == "html" ) {

				// Record the old HTML
				var oldHtml = element.html();
				modifications[i].original = htmlentities(oldHtml, "ENT_QUOTES"); console.log('OLD', modifications[i].original);


				// Apply the modification
				var newHTML = html_entity_decode(modification.modification); console.log('NEW', newHTML);
				element.html( newHTML ).attr('data-revisionary-edited', "1").attr('data-revisionary-showing-changes', "1");

				// Update the pin status
				$('#pins > pin[data-pin-id="'+modification.pin_ID+'"]').attr('data-revisionary-edited', "1").attr('data-revisionary-showing-changes', "1");

			}


		});



		// PAGE INTERACTIONS
		// Hide the loading overlay
		$('#loading').fadeOut();

		// Close all the tabs
		$('.opener').each(function() {

			toggleTab( $(this), true );

		});



		// Body class !!! ???
		$('body').addClass('ready');


	    // Show current process on loading overlay with progress bar
	    // ...


    	// Remove the loading overlay ???
		// $('#loading-overlay').fadeOut();


	    // Update the title
		if ( iframe.find('title').length ) {
			$('title').text( "Revise Page: " + iframe.find('title').text() );
		}


		// MOUSE ACTIONS
	    iframe.on('mousemove', function(e) { // Detect the mouse moves in frame


			// Iframe offset
			offset = $('#the-page').offset();
			//console.log('OFFSET:', offset);


		    // Mouse coordinates according to the screen - NO NEED FOR NOW !!!
		    screenX = e.clientX * iframeScale + offset.left;
		    screenY = e.clientY * iframeScale + offset.top;

		    // Mouse coordinates according to the iframe container
		    containerX = e.clientX * iframeScale;
		    containerY = e.clientY * iframeScale;

		    // Follow the mouse cursor
			$('.mouse-cursor').css({
				left:  containerX,
				top:   containerY
			});


/*
			console.log('Screen: ', screenX, screenY);
			console.log('Container: ', containerX, containerY);
*/



		    // Focused Element
	        focused_element = $(e.target);
	        focused_element_index = focused_element.attr('data-revisionary-index');
	        focused_element_has_index = focused_element_index != null ? true : false;
	        focused_element_text = focused_element.clone().children().remove().end().text(); // Gives only text, without inner html
	        focused_element_html = focused_element.html();
	        focused_element_children = focused_element.children();
	        focused_element_grand_children = focused_element_children.children();
	        focused_element_pin = $('#pins > pin[data-pin-type="live"][data-revisionary-index="'+ focused_element_index +'"]');
			focused_element_edited_parents = focused_element.parents('[data-revisionary-index][data-revisionary-edited]');
			focused_element_has_edited_child = focused_element.find('[data-revisionary-index][data-revisionary-edited]').length;


			// Work only if cursor is active
			if (cursorActive && !hoveringPin) {


				// REFOCUS WORKS:
				// Re-focus if only child element has no child and has content
				if (
					focused_element_text == "" && // Focused element has no content
					focused_element_children.length == 1 && // Has only one child
					focused_element_grand_children.length == 0 && // No grand child
					focused_element_children.first().text().trim() != "" // Grand child should have content
				) {

					// Re-focus to the child element
					focused_element = focused_element_children.first();
			        focused_element_index = focused_element.attr('data-revisionary-index');
			        focused_element_has_index = focused_element_index != null ? true : false;
			        focused_element_text = focused_element.clone().children().remove().end().text(); // Gives only text, without inner html
					focused_element_html = focused_element.html();
			        focused_element_children = focused_element.children();
			        focused_element_grand_children = focused_element_children.children();
					focused_element_pin = $('#pins > pin[data-revisionary-index="'+ focused_element_index +'"]');
					focused_element_edited_parents = focused_element.parents('[data-revisionary-index][data-revisionary-edited]');
					focused_element_has_edited_child = focused_element.find('[data-revisionary-index][data-revisionary-edited]').length;

				}


				// EDITABLE CHECKS
				// Re-focus to the edited element if this is child of it: <p data-edited="1"><b>Lorem
				hoveringText = false;
		        focused_element_editable = false;
		        focused_element_html_editable = false;
				if (focused_element_edited_parents.length) {

					// Re-focus to the parent edited element
					focused_element = $(focused_element_edited_parents[0]);
			        focused_element_index = focused_element.attr('data-revisionary-index');
			        focused_element_has_index = focused_element_index != null ? true : false;
			        focused_element_text = focused_element.clone().children().remove().end().text(); // Gives only text, without inner html
					focused_element_html = focused_element.html();
			        focused_element_children = focused_element.children();
			        focused_element_grand_children = focused_element_children.children();
					focused_element_pin = $('#pins > pin[data-revisionary-index="'+ focused_element_index +'"]');
					focused_element_edited_parents = focused_element.parents('[data-revisionary-index][data-revisionary-edited]');
					focused_element_has_edited_child = focused_element.find('[data-revisionary-index][data-revisionary-edited]').length;

					hoveringText = true;
					focused_element_editable = true; // Obviously Text Editable
					focused_element_html_editable = true;

					console.log('Already edited element: ', focused_element.prop("tagName"));

				}


				// Check element text editable: <p>Lorem ipsum dolor sit amet...
		        if (
			        easy_html_elements.indexOf( focused_element.prop("tagName") ) != -1 && // In easy HTML elements?
		        	focused_element_text.trim() != "" && // If not empty
		        	focused_element.html() != "&nbsp;" && // If really not empty
		        	focused_element_children.length == 0 // If doesn't have any child
		        ) {

					hoveringText = true;
					focused_element_editable = true; // Obviously Text Editable
					focused_element_html_editable = true;
					console.log( '* Obviously Text Editable: ' + focused_element.prop("tagName") );
					console.log( 'Focused Element Text: ' + focused_element_text );

				}



				// Check element image editable: <img src="#">...
				hoveringImage = false;
		        if ( focused_element.prop("tagName") == "IMG" ) {

					hoveringImage = true;
					focused_element_editable = true; // Obviously Image Editable
					console.log( '* Obviously Image Editable: ' + focused_element.prop("tagName") );
					console.log( 'Focused Element Image: ' + focused_element.attr('src') );

				}



				// Check if element has children but doesn't have grand children: <p>Lorem ipsum <a href="#">dolor</a> sit amet...
				if (
					focused_element_children.length > 0 && // Has child
					focused_element_grand_children.length == 0 && // No grand child
					focused_element_text.trim() != "" && // And, also have to have text
					focused_element.html() != "&nbsp;" // And, also have to have text
				) {


					// Also check the children's tagname
					var hardToEdit = true;
					focused_element_children.each(function() {

						// In easy HTML elements?
						if (easy_with_br.indexOf( $(this).prop("tagName") ) != -1 ) hardToEdit = false;

					});

					if (!hardToEdit) {

						hoveringText = true;
						focused_element_editable = true;
						focused_element_html_editable = true;
						console.log( '* Text Editable (No Grand Child): ' + focused_element.prop("tagName") );
						console.log( 'Focused Element Text: ' + focused_element_text );

					}

				}



				// Chech if element has only one grand child and it doesn't have any child: <p>Lorem ipsum <a href="#"><strong>dolor</strong></a> sit amet...
				if (
					focused_element_children.length > 0 && // Has child
					focused_element_grand_children.length > 0 && // Has grand child
					focused_element_text.trim() != "" && // And, also have to have text
					focused_element.html() != "&nbsp;" // And, also have to have text
				) {


					// Also check the children's tagname
					var easyToEdit = false;
					focused_element_children.each(function() {

						var child = $(this);
						var grandChildren = child.children();


						if (
							easy_with_br.indexOf( child.prop("tagName") ) != -1 && // Child is easy to edit
							grandChildren.length == 1 && // Grand child has no more than 1 child
							easy_with_br.indexOf( grandChildren.first().prop("tagName") ) != -1 // And that guy is easy to edit as well
						)

							easyToEdit = true;

					});

					if (easyToEdit) {

						hoveringText = true;
						focused_element_editable = true;
						focused_element_html_editable = true;
						console.log( '* Text Editable (One Grand Child): ' + focused_element.prop("tagName") );
						console.log( 'Focused Element Text: ' + focused_element_text );

					}


				}



				// Check the submit buttons: <input type="submit | reset">...
				hoveringButton = false;
		        if (
		        	focused_element.prop("tagName") == "INPUT" &&
		        	(
		        		focused_element.attr("type") == "submit" ||
		        		focused_element.attr("type") == "reset"
		        	)
		        ) {

					hoveringButton = true;
					focused_element_editable = true; // Obviously Image Editable
					console.log( '* Button Editable: ' + focused_element.prop("tagName") );
					console.log( 'Focused Button Text: ' + focused_element.attr('value') );

				}



				// Check if it doesn't have any element index: <p data-revisionary-index="16">...
				if (focused_element_editable && !focused_element_has_index) {

					focused_element_editable = false;
					focused_element_html_editable = false;
					console.log( '* Element editable but NO INDEX: ' + focused_element.prop("tagName") );

				}



				// If focused element has edited child, don't focus it
				if (focused_element_has_edited_child) {

					focused_element_editable = false;
					focused_element_html_editable = false;
					console.log( '* Element editable but there is edited child: ' + focused_element.prop("tagName") );

				}



				// See what am I focusing
				//console.log("CURRENT FOCUSED: ", focused_element.prop("tagName"), focused_element_index );
				//console.log("CURRENT FOCUSED EDITABLE: ", focused_element_editable );



				// Clean Other Outlines
				iframe.find('body *').css('outline', '');

				// Reset the pin opacity
				$('#pins > pin').css('opacity', '');



				// LIVE REACTIONS
				focused_element_has_live_pin = false;
				if (currentPinType == "live") {

					if (focused_element_editable) {

						switchCursorType('live');
						outline(focused_element, currentPinPrivate);


						// Check if it already has a pin
						if ( focused_element_pin.length ) {

							focused_element_has_live_pin = true;


							// Point to the pin
							$('#pins > pin:not([data-pin-type="live"][data-revisionary-index="'+ focused_element_index +'"])').css('opacity', '0.2');


							// Color the element that has a pin according to the pin type
							outline(focused_element, focused_element_pin.attr('data-pin-private'));


							console.log('This element already has a live pin.');

						}


					} else {

						// If not editable, switch back to the standard pin
						switchCursorType('standard');

					}

				} // If current pin type is 'live'



			} // If cursor active


		}).on('click', function(e) { // Detect the mouse clicks in frame


			// If cursor
			if (cursorActive) {


				if (focused_element_has_live_pin) {


					// Open the pin window !!!
					openPinWindow(focused_element_pin.attr('data-pin-x'), focused_element_pin.attr('data-pin-y'), focused_element_pin.attr('data-pin-id'));


				} else {

					// Add a pin and open a pin window
					putPin(e.pageX, e.pageY);

				}



			}


			// Prevent clicking something?
			e.preventDefault();
			return false;

		}).on('scroll', function(e) { // Detect the scroll to re-position pins

			scrollOffset_top = $(this).scrollTop();
			scrollOffset_left = $(this).scrollLeft();


			scrollX = scrollOffset_left * iframeScale;
			scrollY = scrollOffset_top * iframeScale;


		    // Re-Locate the pins
		    relocatePins();

		});


		$(window).on('resize', function(e) { // Detect the scroll to re-position pins

			scrollOffset_top = iframe.scrollTop();
			scrollOffset_left = iframe.scrollLeft();


			scrollX = scrollOffset_left * iframeScale;
			scrollY = scrollOffset_top * iframeScale;


		    // Re-Locate the pins
		    relocatePins();

		});



	});


}


// FUNCTION: Tab Toggler
function toggleTab(tab, forceClose = false) {

	var speed = 500;

	var container = tab.parent();
	var containerWidth = container.outerWidth();
	var sideElement = tab.parent().parent();


	if ( sideElement.hasClass('top-left') || sideElement.hasClass('bottom-left') ) {

		if (container.hasClass('open') || forceClose) {

			sideElement.animate({
				left: -containerWidth,
			}, speed, function() {
				container.removeClass('open');
			});

		} else {

			sideElement.animate({
				left: 0,
			}, speed, function() {
				container.addClass('open');
			});

		}

	} else {

		if (container.hasClass('open') || forceClose) {

			sideElement.animate({
				right: -containerWidth,
			}, speed, function() {
				container.removeClass('open');
			});

		} else {

			sideElement.animate({
				right: 0,
			}, speed, function() {
				container.addClass('open');
			});

		}

	}

}


// FUNCTION: Color the element
function outline(element, private_pin) {

	element.css('outline', '2px dashed ' + (private_pin == 1 ? '#FC0FB3' : '#7ED321'), 'important');

}


// FUNCTION: Switch to a different pin mode
function switchPinType(pinType, pinPrivate) {

	log('Switched Pin Type: ', pinType);
	log('Switched Pin Private? ', pinPrivate);


	currentPinType = pinType;
	currentPinPrivate = pinPrivate;


	// Change the activator color
	activator.attr('data-pin-type', currentPinType).attr('data-pin-private', currentPinPrivate);


	// Change the cursor color
	switchCursorType(pinType == "live" ? 'standard' : pinType, currentPinPrivate);


	// Close the type selector
	if (pinTypeSelectorOpen) togglePinTypeSelector(true);


	// Close the open pin window
	if (pinWindowOpen) closePinWindow();

}


// FUNCTION: Switch to a different cursor mode
function switchCursorType(cursorType) {

	log(cursorType);

	cursor.attr('data-pin-type', cursorType).attr('data-pin-private', currentPinPrivate);
	currentCursorType = cursorType;

}


// FUNCTION: Toggle Inspect Mode
function toggleCursorActive(forceClose = false, forceOpen = false) {

	cursor.stop();
	var cursorVisible = cursor.is(":visible");

	if ( (cursorActive || forceClose) && !forceOpen ) {

		// Deactivate
		activator.removeClass('active');

		// Hide the cursor
		if (cursorVisible) cursor.fadeOut();

		// Show the original cursor
		iframe.find('body, body *').css('cursor', '', '');

		// Enable all the links
	    // ...


		cursorActive = false;
		focused_element = null;

	} else {


		// Activate
		activator.addClass('active');

		// Show the cursor
		if (!cursorVisible && !pinWindowOpen) cursor.fadeIn();

		// Hide the original cursor
		iframe.find('body, body *').css('cursor', 'none', 'important');

		// Disable all the links
	    // ...


		cursorActive = true;
		if (pinTypeSelectorOpen) togglePinTypeSelector(true); // Force Close

	}


	// Close the open pin window
	if (pinWindowOpen) closePinWindow();

}


// FUNCTION: Toggle Pin Mode Selector
function togglePinTypeSelector(forceClose = false) {

	if (pinTypeSelectorOpen || forceClose) {

		pinTypeSelector.removeClass('open');
		pinTypeSelector.parent().removeClass('selector-open');
		$('#pin-type-selector').fadeOut();
		pinTypeSelectorOpen = false;
		if (!cursorActive) toggleCursorActive();

	} else {

		pinTypeSelector.addClass('open');
		pinTypeSelector.parent().addClass('selector-open');
		$('#pin-type-selector').fadeIn();
		pinTypeSelectorOpen = true;
		if (cursorActive) toggleCursorActive(true);

	}

}


// FUNCTION: Change the pin number on cursor
function changePinNumber(pinNumber) {

	cursor.text(pinNumber);
	currentPinNumber = pinNumber;

}


// FUNCTION: Re-Locate Pins
function relocatePins(pin_selector = null, x = null, y = null) {


	if ( pin_selector ) {

	    var scrolled_pin_x = x > 0 ? x : 0;
	    var scrolled_pin_y = y > 0 ? y : 0;

	    scrolled_pin_x = x < iframeWidth - 45 ? scrolled_pin_x : iframeWidth - 45;
	    scrolled_pin_y = y < iframeHeight - 45 ? scrolled_pin_y : iframeHeight - 45;

		pin_selector.css('left', scrolled_pin_x + "px");
		pin_selector.css('top', scrolled_pin_y + "px");


		var realPinX = (scrolled_pin_x / iframeScale) + scrollOffset_left;
		var realPinY = (scrolled_pin_y / iframeScale) + scrollOffset_top;


		// Update the registered pin location
	    pin_selector.attr('data-pin-x', realPinX);
		pin_selector.attr('data-pin-y', realPinY );


		// Update the registered pin window location as well, only if current pin is moving
		if ( pin_selector.attr('data-pin-id') == pinWindow.attr('data-pin-id') ) {

			pinWindow.attr('data-pin-x', realPinX);
			pinWindow.attr('data-pin-y', realPinY);

		}


	} else {


	    $('#pins > pin').each(function() {

		    var pin = $(this);


		    var pin_x = parseInt(pin.attr('data-pin-x')) * iframeScale;
		    var pin_y = parseInt(pin.attr('data-pin-y')) * iframeScale;


		    var scrolled_pin_x = pin_x - scrollX;
		    var scrolled_pin_y = pin_y - scrollY;


		    pin.css('left', scrolled_pin_x + "px");
			pin.css('top', scrolled_pin_y + "px");

	    });


	}


	// Current pin window location
	var window_x = parseInt(pinWindow.attr('data-pin-x')) * iframeScale;
	var window_y = parseInt(pinWindow.attr('data-pin-y')) * iframeScale;


    var scrolled_window_x = window_x + offset.left - scrollX + 50;
    var scrolled_window_y = window_y + offset.top - scrollY + 50;


	var spaceWidth = offset.left + iframeWidth + offset.left - 15;
	var spaceHeight = offset.top + iframeHeight + offset.top - 15;


    var new_scrolled_window_x = scrolled_window_x < spaceWidth - pinWindowWidth ? scrolled_window_x : spaceWidth - pinWindowWidth;
    var new_scrolled_window_y = scrolled_window_y < spaceHeight - pinWindowHeight ? scrolled_window_y : spaceHeight - pinWindowHeight;


	// Change the side of the window
	if (
		scrolled_window_x >= spaceWidth - pinWindowWidth &&
		scrolled_window_y >= spaceHeight - pinWindowHeight
	) {

		//console.log('OUCH!');
		new_scrolled_window_x = scrolled_window_x - pinWindowWidth - 55;

	}


	// Make the pin window stay after scrolling up
	if (scrolled_window_y > new_scrolled_window_y + pinWindowHeight) {

		//console.log('GOODBYE!');
		new_scrolled_window_y = scrolled_window_y - pinWindowHeight;

	}


	// Relocate the pin window
	pinWindow.css('left', new_scrolled_window_x + "px");
	pinWindow.css('top', new_scrolled_window_y + "px");

}


// FUNCTION: Re-Index Pins
function reindexPins() {


    $('#pins > pin').each(function(i) {

	    var pin = $(this);

		pin.text(i+1);

    });


    // Update the current pin number on cursor
    changePinNumber(currentPinNumber - 1);


}


// FUNCTION: Put a pin to cordinates
function putPin(pinX, pinY) {

	// Put it just on the pointer point
	pinX = pinX - 45/2;
	pinY = pinY - 45/2;


	console.log('Put the Pin #' + currentPinNumber, pinX, pinY, currentCursorType, currentPinPrivate, focused_element_index);


	var temporaryPinID = makeID();


	// Add the pin to the DOM
	$('#pins').append(
		newPinTemplate(pinX, pinY, temporaryPinID, user_ID)
	);



	// Open the pin window
	openPinWindow(pinX, pinY, temporaryPinID);



    // Add pin to the DB
    console.log('Add pin to the DB !!!');


	// Start the process
	var newPinProcessID = newProcess();

    $.post(ajax_url, {
		'type'	  	 		: 'pin-add',
		'nonce'	  	 		: pin_nonce,
		'pin_x' 	 		: pinX,
		'pin_y' 	 		: pinY,
		'pin_type' 	 		: currentCursorType,
		'pin_private'		: currentPinPrivate,
		'pin_element_index' : focused_element_index,
		'pin_version_ID'	: version_ID,
	}, function(result){

		console.log(result.data);

		var realPinID = result.data.real_pin_ID;

		console.log('REAL PIN ID: '+realPinID);


		// Update the pin ID !!!
		$('#pins > pin[data-pin-id="'+temporaryPinID+'"]').attr('data-pin-id', realPinID);
		$('#pin-window').attr('data-pin-id', realPinID);


		if (currentCursorType == "live") {

			var editedElement = iframe.find('[data-revisionary-index="'+focused_element_index+'"]');

			// Add edited status to the DOM
			editedElement.attr('data-revisionary-edited', "0");


			// Add to the modifications list
			modifications[modifications.length] = {
				element_index: focused_element_index,
				pin_ID: realPinID,
				modification_type: "html",
				modification: null,
				original: htmlentities( editedElement.html(), "ENT_QUOTES")
			};

		}

		// Remove the loading text on pin window
		$('#pin-window').removeClass('loading');


		// Finish the process
		endProcess(newPinProcessID);

	}, 'json');




	// Re-Locate the pins
	relocatePins();


	// Increase the pin number
	changePinNumber(currentPinNumber + 1);

}


// FUNCTION: Open the pin window
function openPinWindow(pin_x, pin_y, pin_ID) {


	var thePin = $('#pins > pin[data-pin-id="'+ pin_ID +'"]');
	var thePinType = thePin.attr('data-pin-type');
	var thePinPrivate = thePin.attr('data-pin-private');
	var thePinComplete = thePin.attr('data-pin-complete');
	var theIndex = thePin.attr('data-revisionary-index');
	var thePinText = thePinPrivate == '1' ? 'PRIVATE COMMENT' : 'ONLY COMMENT';
	var thePinModified = thePin.attr('data-revisionary-edited');
	var thePinShowingChanges = thePin.attr('data-revisionary-showing-changes');
	var originalContent = "";


	// Previous state of window
	pinWindowWasOpen = pinWindowOpen;


	// Previous state of cursor
	if (!pinWindowWasOpen) cursorWasActive = cursorActive;


	// Close the previous window
	closePinWindow();


	// Disable the inspector
	toggleCursorActive(true); // Force deactivate


	// Add the pin window data !!!
	pinWindow.attr('data-pin-type', thePinType);
	pinWindow.attr('data-pin-private', thePinPrivate);
	pinWindow.attr('data-pin-complete', thePinComplete);
	pinWindow.attr('data-pin-x', thePin.attr('data-pin-x'));
	pinWindow.attr('data-pin-y', thePin.attr('data-pin-y'));
	pinWindow.attr('data-pin-id', pin_ID);
	pinWindow.attr('data-revisionary-edited', thePinModified);
	pinWindow.attr('data-revisionary-showing-changes', thePinShowingChanges);
	pinWindow.attr('data-revisionary-index', theIndex);


	// Update the pin type section
	pinWindow.find('pin.chosen-pin').attr('data-pin-type', thePinType);
	pinWindow.find('pin.chosen-pin').attr('data-pin-private', thePinPrivate);
	pinWindow.find('pin.chosen-pin + span > span').text(thePinText);


	// Arrange the convertor options
	pinWindow.find('ul.type-convertor > li').show();
	pinWindow.find('ul.type-convertor > li > a > pin[data-pin-type="'+thePinType+'"][data-pin-private="'+thePinPrivate+'"]').parent().parent().hide();

	// Also remove the live option on comments
	if (thePinType == "standard")
		pinWindow.find('ul.type-convertor > li > a > pin[data-pin-type="live"][data-pin-private="0"]').parent().parent().hide();


	// Hide the editor
	pinWindow.find('.content-editor').hide();


	// If on 'Live' mode
	if (thePinType == 'live') {

		thePinText = thePinPrivate == '1' ? 'PRIVATE LIVE' : 'LIVE EDIT';


		// Update the pin type section
		pinWindow.find('pin.chosen-pin + span > span').text(thePinText);


		// Show the content editor
		pinWindow.find('.content-editor').show();



		var origContent = iframe.find('[data-revisionary-index="'+ theIndex +'"]:not([data-revisionary-showing-changes])');



		// MODIFICATION FINDER
		var modification = modifications.find(function(modification) {
			return modification.pin_ID == pin_ID ? true : false;
		});

		// Show the changed HTML content on the editor
		if (modification && modification.modification != null)
			pinWindow.find('.edit-content.changes').html( html_entity_decode (modification.modification) );

		// Add the original HTML content
		if (modification && modification.original != null)
			originalContent = html_entity_decode (modification.original);

		// If it's untouched DOM
		if ( origContent.length ) {
			originalContent = origContent.html();

			// Default change editor
			pinWindow.find('.edit-content.changes').html( origContent.html() );
		}


		// Update the original content
		pinWindow.find('.edit-content.original').html( originalContent );

	}


	// Relocate the window
	relocatePins();


	// Reveal it
	pinWindow.addClass('active');
	pinWindowOpen = true;


	// Don't remove the loading text if newly added
	if ( $.isNumeric(pin_ID) )
		pinWindow.removeClass('loading');


	// Show the pin
	$('#pins > pin:not([data-pin-id="'+ pin_ID +'"])').css('opacity', '0.2');


	// Update the pin window sizes
	pinWindowWidth = pinWindow.outerWidth();
	pinWindowHeight = pinWindow.outerHeight();

}


// FUNCTION: Close pin window
function closePinWindow() {

	// Previous state of window
	pinWindowWasOpen = pinWindowOpen;

	// Hide it
	pinWindow.removeClass('active');
	pinWindowOpen = false;


	// Add the loading text after loading
	pinWindow.addClass('loading');


	// Reset the pin opacity
	$('#pins > pin').css('opacity', '');


	if (cursorWasActive) toggleCursorActive(false, true); // Force Open

}


// FUNCTION: Remove a pin
function removePin(pin_ID) {


    // Add pin to the DB
    console.log('Remove the pin #' + pin_ID + ' from DB!!');


	// Start the process
	var newPinProcessID = newProcess();

    $.post(ajax_url, {
		'type'	  	: 'pin-remove',
		'nonce'	  	: pin_nonce,
		'pin_ID'	: pin_ID
	}, function(result){

		console.log(result.data);


		// Close the pin window
		closePinWindow();


		// Revert the changes
		var modification = modifications.find(function(modification) {
			return modification.pin_ID == pin_ID ? true : false;
		});
		var modificationIndex = modifications.indexOf(modification);

		console.log(modification);

		if (modification) {

			var modifiedElement = iframe.find('[data-revisionary-index="'+ modification.element_index +'"]');

			// Add the original HTML content
			if (modification.original != null)
				modifiedElement.html( html_entity_decode (modification.original) );

			// Remove the edited status from DOM element
			modifiedElement.removeAttr('data-revisionary-edited').removeAttr('data-revisionary-showing-changes');

			// Delete from the list
			modifications.splice(modificationIndex, 1);


		}



		// Remove the pin from DOM
		$('#pins > pin[data-pin-id="'+pin_ID+'"]').remove();


		// Re-Index the pin counts
		reindexPins();


		// Finish the process
		endProcess(newPinProcessID);

	}, 'json');


}


// FUNCTION: Complete/Incomplete a pin
function completePin(pin_ID, complete) {


    // Add pin to the DB
    console.log( (complete ? 'Complete' : 'Incomplete') +' the pin #' + pin_ID + ' on DB!!');


	// Start the process
	var newPinProcessID = newProcess();

    $.post(ajax_url, {
		'type'	  	 		: 'pin-complete',
		'complete' 	 		: (complete ? 'complete' : 'incomplete'),
		'nonce'	  	 		: pin_nonce,
		'pin_ID'			: pin_ID
	}, function(result){

		console.log(result.data);

		// Update the pin status
		$('#pins > pin[data-pin-id="'+pin_ID+'"]').attr('data-pin-complete', (complete ? '1' : '0'));


		// Update the pin window status
		pinWindow.attr('data-pin-complete', (complete ? '1' : '0'));


		// Finish the process
		endProcess(newPinProcessID);

	}, 'json');


}


// FUNCTION: Save a modification
function saveModification(pin_ID, modification, modification_type = "html") {


    // Add pin to the DB
    console.log( 'Save modification for the pin #' + pin_ID + ' on DB!!');


	// Start the process
	var newPinProcessID = newProcess();

    $.post(ajax_url, {
		'type'	  	 		: 'pin-modify',
		'modification' 	 	: modification,
		'modification_type'	: modification_type,
		'nonce'	  	 		: pin_nonce,
		'pin_ID'			: pin_ID
	}, function(result){

		console.log(result.data);

		// Update the pin status
		$('#pins > pin[data-pin-id="'+pin_ID+'"]').attr('data-revisionary-edited', "1");


		// Update the pin window status
		pinWindow.attr('data-revisionary-edited', "1").attr('data-revisionary-showing-changes', "1");


		// Finish the process
		endProcess(newPinProcessID);

	}, 'json');


}


// FUNCTION: Toggle content edits
function toggleContentEdit(pin_ID) {

	var isShowingChanges = pinWindow.attr('data-revisionary-showing-changes') == "1" ? true : false;


	// MODIFICATION FINDER
	var modification = modifications.find(function(modification) {
		return modification.pin_ID == pin_ID ? true : false;
	});


	if (modification) {

		// Change the content on DOM
		iframe.find('[data-revisionary-index="'+modification.element_index+'"]')
			.html( html_entity_decode( (isShowingChanges ? modification.original : modification.modification) ) )
			.attr('data-revisionary-showing-changes', (isShowingChanges ? "0" : "1") );

		// Update the Pin Window and Pin info
		pinWindow.attr('data-revisionary-showing-changes', (isShowingChanges ? "0" : "1"));
		$('#pins > pin[data-pin-id="'+pin_ID+'"]').attr('data-revisionary-showing-changes', (isShowingChanges ? "0" : "1"));

	}

}


// FUNCTION: Toggle pin window
function togglePinWindow(pin_x, pin_y, pin_ID) {

	if (pinWindowOpen && pinWindow.attr('data-pin-id') == pin_ID) closePinWindow();
	else openPinWindow(pin_x, pin_y, pin_ID);

}


// TEMPLATE: Pin template
function newPinTemplate(pin_x, pin_y, pin_ID, user_ID) {

	return '\
		<pin \
			class="pin big" \
			data-pin-type="'+currentCursorType+'" \
			data-pin-private="'+currentPinPrivate+'" \
			data-pin-complete="0" \
			data-pin-user-id="'+user_ID+'" \
			data-pin-id="'+pin_ID+'" \
			data-pin-x="'+pin_x+'" \
			data-pin-y="'+pin_y+'" \
			data-revisionary-edited="0" \
			data-revisionary-showing-changes="1" \
			data-revisionary-index="'+focused_element_index+'" \
			style="top: '+pin_y+'px; left: '+pin_x+'px;" \
		>'+currentPinNumber+'</pin> \
	';

}



// FUNCTION: ID Creator
function makeID() {
	var text = "";
	var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";

	for (var i = 0; i < 5; i++)
		text += possible.charAt(Math.floor(Math.random() * possible.length));

	return text;
}


// Console log shortcut
function log(log, arg1) {
	//console.log(log);
}










// HELPERS
function get_html_translation_table (table, quote_style) {
  //  discuss at: http://phpjs.org/functions/get_html_translation_table/
  // original by: Philip Peterson
  //  revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // bugfixed by: noname
  // bugfixed by: Alex
  // bugfixed by: Marco
  // bugfixed by: madipta
  // bugfixed by: Brett Zamir (http://brett-zamir.me)
  // bugfixed by: T.Wild
  // improved by: KELAN
  // improved by: Brett Zamir (http://brett-zamir.me)
  //    input by: Frank Forte
  //    input by: Ratheous
  //        note: It has been decided that we're not going to add global
  //        note: dependencies to php.js, meaning the constants are not
  //        note: real constants, but strings instead. Integers are also supported if someone
  //        note: chooses to create the constants themselves.
  //   example 1: get_html_translation_table('HTML_SPECIALCHARS');
  //   returns 1: {'"': '&quot;', '&': '&amp;', '<': '&lt;', '>': '&gt;'}

  var entities = {},
    hash_map = {},
    decimal
  var constMappingTable = {},
    constMappingQuoteStyle = {}
  var useTable = {},
    useQuoteStyle = {}

  // Translate arguments
  constMappingTable[0] = 'HTML_SPECIALCHARS'
  constMappingTable[1] = 'HTML_ENTITIES'
  constMappingQuoteStyle[0] = 'ENT_NOQUOTES'
  constMappingQuoteStyle[2] = 'ENT_COMPAT'
  constMappingQuoteStyle[3] = 'ENT_QUOTES'

  useTable = !isNaN(table) ? constMappingTable[table] : table ? table.toUpperCase() : 'HTML_SPECIALCHARS'
  useQuoteStyle = !isNaN(quote_style) ? constMappingQuoteStyle[quote_style] : quote_style ? quote_style.toUpperCase() :
    'ENT_COMPAT'

  if (useTable !== 'HTML_SPECIALCHARS' && useTable !== 'HTML_ENTITIES') {
    throw new Error('Table: ' + useTable + ' not supported')
    // return false;
  }

  entities['38'] = '&amp;'
  if (useTable === 'HTML_ENTITIES') {
    entities['160'] = '&nbsp;'
    entities['161'] = '&iexcl;'
    entities['162'] = '&cent;'
    entities['163'] = '&pound;'
    entities['164'] = '&curren;'
    entities['165'] = '&yen;'
    entities['166'] = '&brvbar;'
    entities['167'] = '&sect;'
    entities['168'] = '&uml;'
    entities['169'] = '&copy;'
    entities['170'] = '&ordf;'
    entities['171'] = '&laquo;'
    entities['172'] = '&not;'
    entities['173'] = '&shy;'
    entities['174'] = '&reg;'
    entities['175'] = '&macr;'
    entities['176'] = '&deg;'
    entities['177'] = '&plusmn;'
    entities['178'] = '&sup2;'
    entities['179'] = '&sup3;'
    entities['180'] = '&acute;'
    entities['181'] = '&micro;'
    entities['182'] = '&para;'
    entities['183'] = '&middot;'
    entities['184'] = '&cedil;'
    entities['185'] = '&sup1;'
    entities['186'] = '&ordm;'
    entities['187'] = '&raquo;'
    entities['188'] = '&frac14;'
    entities['189'] = '&frac12;'
    entities['190'] = '&frac34;'
    entities['191'] = '&iquest;'
    entities['192'] = '&Agrave;'
    entities['193'] = '&Aacute;'
    entities['194'] = '&Acirc;'
    entities['195'] = '&Atilde;'
    entities['196'] = '&Auml;'
    entities['197'] = '&Aring;'
    entities['198'] = '&AElig;'
    entities['199'] = '&Ccedil;'
    entities['200'] = '&Egrave;'
    entities['201'] = '&Eacute;'
    entities['202'] = '&Ecirc;'
    entities['203'] = '&Euml;'
    entities['204'] = '&Igrave;'
    entities['205'] = '&Iacute;'
    entities['206'] = '&Icirc;'
    entities['207'] = '&Iuml;'
    entities['208'] = '&ETH;'
    entities['209'] = '&Ntilde;'
    entities['210'] = '&Ograve;'
    entities['211'] = '&Oacute;'
    entities['212'] = '&Ocirc;'
    entities['213'] = '&Otilde;'
    entities['214'] = '&Ouml;'
    entities['215'] = '&times;'
    entities['216'] = '&Oslash;'
    entities['217'] = '&Ugrave;'
    entities['218'] = '&Uacute;'
    entities['219'] = '&Ucirc;'
    entities['220'] = '&Uuml;'
    entities['221'] = '&Yacute;'
    entities['222'] = '&THORN;'
    entities['223'] = '&szlig;'
    entities['224'] = '&agrave;'
    entities['225'] = '&aacute;'
    entities['226'] = '&acirc;'
    entities['227'] = '&atilde;'
    entities['228'] = '&auml;'
    entities['229'] = '&aring;'
    entities['230'] = '&aelig;'
    entities['231'] = '&ccedil;'
    entities['232'] = '&egrave;'
    entities['233'] = '&eacute;'
    entities['234'] = '&ecirc;'
    entities['235'] = '&euml;'
    entities['236'] = '&igrave;'
    entities['237'] = '&iacute;'
    entities['238'] = '&icirc;'
    entities['239'] = '&iuml;'
    entities['240'] = '&eth;'
    entities['241'] = '&ntilde;'
    entities['242'] = '&ograve;'
    entities['243'] = '&oacute;'
    entities['244'] = '&ocirc;'
    entities['245'] = '&otilde;'
    entities['246'] = '&ouml;'
    entities['247'] = '&divide;'
    entities['248'] = '&oslash;'
    entities['249'] = '&ugrave;'
    entities['250'] = '&uacute;'
    entities['251'] = '&ucirc;'
    entities['252'] = '&uuml;'
    entities['253'] = '&yacute;'
    entities['254'] = '&thorn;'
    entities['255'] = '&yuml;'
  }

  if (useQuoteStyle !== 'ENT_NOQUOTES') {
    entities['34'] = '&quot;'
  }
  if (useQuoteStyle === 'ENT_QUOTES') {
    entities['39'] = '&#39;'
  }
  entities['60'] = '&lt;'
  entities['62'] = '&gt;'

  // ascii decimals to real symbols
  for (decimal in entities) {
    if (entities.hasOwnProperty(decimal)) {
      hash_map[String.fromCharCode(decimal)] = entities[decimal]
    }
  }

  return hash_map
}

function htmlentities (string, quote_style, charset, double_encode) {
  //  discuss at: http://phpjs.org/functions/htmlentities/
  // original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  //  revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  //  revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // improved by: nobbler
  // improved by: Jack
  // improved by: Rafał Kukawski (http://blog.kukawski.pl)
  // improved by: Dj (http://phpjs.org/functions/htmlentities:425#comment_134018)
  // bugfixed by: Onno Marsman
  // bugfixed by: Brett Zamir (http://brett-zamir.me)
  //    input by: Ratheous
  //  depends on: get_html_translation_table
  //        note: function is compatible with PHP 5.2 and older
  //   example 1: htmlentities('Kevin & van Zonneveld');
  //   returns 1: 'Kevin &amp; van Zonneveld'
  //   example 2: htmlentities("foo'bar","ENT_QUOTES");
  //   returns 2: 'foo&#039;bar'

  var hash_map = this.get_html_translation_table('HTML_ENTITIES', quote_style),
    symbol = ''

  string = string == null ? '' : string + ''

  if (!hash_map) {
    return false
  }

  if (quote_style && quote_style === 'ENT_QUOTES') {
    hash_map["'"] = '&#039;'
  }

  double_encode = double_encode == null || !!double_encode

  var regex = new RegExp('&(?:#\\d+|#x[\\da-f]+|[a-zA-Z][\\da-z]*);|[' +
    Object.keys(hash_map)
    .join('')
    // replace regexp special chars
    .replace(/([()[\]{}\-.*+?^$|\/\\])/g, '\\$1') + ']',
    'g')

  return string.replace(regex, function (ent) {
    if (ent.length > 1) {
      return double_encode ? hash_map['&'] + ent.substr(1) : ent
    }

    return hash_map[ent]
  })
}

function html_entity_decode (string, quote_style) {
  //  discuss at: http://phpjs.org/functions/html_entity_decode/
  // original by: john (http://www.jd-tech.net)
  //    input by: ger
  //    input by: Ratheous
  //    input by: Nick Kolosov (http://sammy.ru)
  // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // improved by: marc andreu
  //  revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  //  revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // bugfixed by: Onno Marsman
  // bugfixed by: Brett Zamir (http://brett-zamir.me)
  // bugfixed by: Fox
  //  depends on: get_html_translation_table
  //   example 1: html_entity_decode('Kevin &amp; van Zonneveld');
  //   returns 1: 'Kevin & van Zonneveld'
  //   example 2: html_entity_decode('&amp;lt;');
  //   returns 2: '&lt;'

  var hash_map = {},
    symbol = '',
    tmp_str = '',
    entity = ''
  tmp_str = string.toString()

  if (false === (hash_map = this.get_html_translation_table('HTML_ENTITIES', quote_style))) {
    return false
  }

  // fix &amp; problem
  // http://phpjs.org/functions/get_html_translation_table:416#comment_97660
  delete (hash_map['&'])
  hash_map['&'] = '&amp;'

  for (symbol in hash_map) {
    entity = hash_map[symbol]
    tmp_str = tmp_str.split(entity)
      .join(symbol)
  }
  tmp_str = tmp_str.split('&#039;')
    .join("'")

  return tmp_str
}