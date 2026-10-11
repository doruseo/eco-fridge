/**
 * Document sidebar panel: brochure PDF file / video YouTube ID.
 */
( function ( wp ) {
	const { registerPlugin } = wp.plugins;
	const { PluginDocumentSettingPanel } = wp.editor;
	const { TextControl, Button } = wp.components;
	const { MediaUpload, MediaUploadCheck } = wp.blockEditor;
	const { useSelect } = wp.data;
	const { useEntityProp } = wp.coreData;
	const el = wp.element.createElement;

	function Panel() {
		const postType = useSelect( ( s ) => s( 'core/editor' ).getCurrentPostType(), [] );
		const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
		if ( ! meta || ( postType !== 'brochure' && postType !== 'video' ) ) {
			return null;
		}

		if ( postType === 'video' ) {
			return el(
				PluginDocumentSettingPanel,
				{ name: 'efb-video', title: 'Video' },
				el( TextControl, {
					label: 'YouTube video ID or URL',
					value: meta._efb_youtube_id || '',
					onChange: ( v ) => setMeta( { ...meta, _efb_youtube_id: v } ),
				} )
			);
		}

		return el(
			PluginDocumentSettingPanel,
			{ name: 'efb-brochure', title: 'Brochure file' },
			el( TextControl, {
				label: 'PDF URL',
				value: meta._efb_file_url || '',
				onChange: ( v ) => setMeta( { ...meta, _efb_file_url: v } ),
			} ),
			el(
				MediaUploadCheck,
				null,
				el( MediaUpload, {
					allowedTypes: [ 'application/pdf' ],
					onSelect: ( m ) => setMeta( { ...meta, _efb_file_url: m.url } ),
					render: ( { open } ) => el( Button, { variant: 'secondary', onClick: open }, 'Select PDF from media library' ),
				} )
			)
		);
	}

	registerPlugin( 'efb-media-meta', { render: Panel } );
} )( window.wp );
