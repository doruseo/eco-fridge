function attachToggle(attachHere, componentsToToggle, componentsToHide) {
    $(attachHere).click(function (e) {
        e.preventDefault();
        $(componentsToToggle).toggleClass('closed');
        $(componentsToHide).addClass('closed');
        //only show contacts if the toggle closed the component
        var showContacts = $(componentsToToggle).hasClass('closed');
        $('.contacts').toggleClass('contacts-visible', showContacts);
    });
}

$('.contacts').addClass('contacts-visible');

/* toggle menu on mobile */
$('#menu-main').addClass('closed');
attachToggle('.btn-menu', '#menu-main', '#search-panel');

/* toggle search panel on mobile */
$('#search-panel').addClass('closed');
attachToggle('.btn-search-panel', '#search-panel', '#menu-main');


// show modal background 
//
//$('#modal-bg').removeClass('bg-visible');
//
//$('.btn-menu').click(function(e){
//    e.preventDefault();
//	$('#modal-bg').toggleClass('bg-visible');
//});