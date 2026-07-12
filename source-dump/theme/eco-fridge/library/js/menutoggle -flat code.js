/* toggle menu on mobile */

$('#menu-navmain').addClass('closed');

$('.btn-menu').click(function(e){
    e.preventDefault();
	$('#menu-navmain').toggleClass('closed');
});


/* toggle search panel on mobile */

$('#search-panel').addClass('closed');

$('.btn-search-panel').click(function(e){
    e.preventDefault();
	$('#search-panel').toggleClass('closed');
});


/* hide contacts when manu or search panel are displayed */

$('.contacts').addClass('contacts-visible');

$('.btn-menu').click(function(e){
    e.preventDefault();
	$('.contacts').toggleClass('contacts-visible');
});
$('.btn-search-panel').click(function(e){
    e.preventDefault();
	$('.contacts').toggleClass('contacts-visible');
});


// show modal background 
//
//$('#modal-bg').removeClass('bg-visible');
//
//$('.btn-menu').click(function(e){
//    e.preventDefault();
//	$('#modal-bg').toggleClass('bg-visible');
//});