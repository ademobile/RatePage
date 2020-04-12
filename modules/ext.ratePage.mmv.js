$(document).ready( function () {
	function addRpWidget() {
		
	}

	function mutationCallback(mutationList, observer) {
		mutationList.forEach((mutation) => {
			mutation.addedNodes.forEach( function (node) {
				if ( node.nodeType === Node.ELEMENT_NODE && node.classList.contains('mw-mmv-wrapper') ) {
					setTimeout(addRpWidget, 500);   // give mmv time to set up or something
				}
			} )
		});
	}

	var observer = new MutationObserver(mutationCallback);
	observer.observe(document.body, {childList: true});
} );
