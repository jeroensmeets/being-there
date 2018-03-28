// console.log( wpChoirUserChoices );

jQuery( document ).ready( function( $ ) {

	$( '.dashicons-wp-choir' ).click( function( _args ) {

		_args.preventDefault();
		_e = _args.target;

		_choices = [ 'no', 'yes', 'edit' ];
		for ( _index in _choices ) {
			_class = _choices[ _index ];
			if ( $( _e ).hasClass( 'dashicons-' + _class ) ) {

				// change icon
				var _next = nextArrayIndex( _class, _choices );
				$( _e ).removeClass( 'dashicons-' + _class );
				$( _e ).addClass( 'dashicons-' + _choices[ _next ] );

				// set value of hidden input field
				var _id = $( _e ).attr('id').replace( 'wp-choir-', '' );
				$( '#wpchoirpresence-' + _id ).val( _next );

				console.log( _id + ' --> ' + _choices[ _next ] );
				if ( 'edit' == _choices[ _next ] ) {
					$( '#wp-choir-comment-' + _id ).show( 'slow' );
				} else {
					$( '#wp-choir-comment-' + _id ).hide( 'slow' );
				}

				// we're always happy
				return true;
			}
		}

	} );

} )

function nextArrayIndex( item, arr ) {
	var idx = arr.indexOf( item );
	return (idx >= 0 && idx <= arr.length - 2) ?  idx + 1 : 0;
}
