/**
 * ECO-Fridge Blocks — editor UI for every block (build-free, uses wp.* globals).
 *
 * Most blocks are dynamic: the sidebar edits the attributes and the canvas shows
 * the real server render (ServerSideRender), so editor and front end match.
 * The Section block uses inner blocks; the Spec Table is edited in place.
 */
( function ( wp ) {
	'use strict';

	const { registerBlockType } = wp.blocks;
	const { createElement: el, Fragment, useEffect } = wp.element;
	const {
		InspectorControls,
		BlockControls,
		MediaUpload,
		MediaUploadCheck,
		useBlockProps,
		useInnerBlocksProps,
		InnerBlocks,
		RichText,
		PanelColorSettings,
	} = wp.blockEditor;
	const {
		PanelBody,
		TextControl,
		TextareaControl,
		ToggleControl,
		SelectControl,
		RangeControl,
		Button,
		ToolbarGroup,
		ToolbarButton,
		Placeholder,
		Notice,
		ComboboxControl,
		FormTokenField,
	} = wp.components;
	const ServerSideRender = wp.serverSideRender;
	const data = window.efbData || { categories: [] };

	/* ------------------------------------------------------------------ */
	/* Shared controls                                                     */
	/* ------------------------------------------------------------------ */

	function ImageField( { label, id, url, onChange } ) {
		return el(
			'div',
			{ className: 'efb-image-field' },
			el( 'p', { className: 'efb-image-field__label' }, label ),
			url ? el( 'img', { src: url, alt: '', className: 'efb-image-field__preview' } ) : null,
			el(
				MediaUploadCheck,
				null,
				el( MediaUpload, {
					allowedTypes: [ 'image' ],
					value: id,
					onSelect: ( m ) => onChange( { id: m.id, url: ( m.sizes && m.sizes.large ? m.sizes.large.url : m.url ), alt: m.alt || '' } ),
					render: ( { open } ) =>
						el(
							'div',
							{ className: 'efb-image-field__buttons' },
							el( Button, { variant: 'secondary', onClick: open }, url ? 'Replace image' : 'Select image' ),
							url ? el( Button, { variant: 'link', isDestructive: true, onClick: () => onChange( { id: 0, url: '', alt: '' } ) }, 'Remove' ) : null
						),
				} )
			)
		);
	}

	/**
	 * Generic repeater for array attributes.
	 * field: { key, label, type: text|textarea|toggle|select|image, options, keys:{id,url,alt} }
	 */
	function Repeater( { items, onChange, fields, newItem, titleKey, itemLabel } ) {
		const list = Array.isArray( items ) ? items : [];
		const update = ( i, patch ) => onChange( list.map( ( it, k ) => ( k === i ? Object.assign( {}, it, patch ) : it ) ) );
		const move = ( i, d ) => {
			const j = i + d;
			if ( j < 0 || j >= list.length ) {
				return;
			}
			const next = list.slice();
			const t = next[ i ];
			next[ i ] = next[ j ];
			next[ j ] = t;
			onChange( next );
		};
		const remove = ( i ) => onChange( list.filter( ( _, k ) => k !== i ) );

		return el(
			'div',
			{ className: 'efb-repeater' },
			list.map( ( item, i ) =>
				el(
					PanelBody,
					{
						key: i,
						title: ( i + 1 ) + '. ' + ( ( titleKey && item[ titleKey ] ) || itemLabel || 'Item' ),
						initialOpen: false,
					},
					fields.map( ( f ) => {
						if ( f.type === 'image' ) {
							const k = f.keys || { id: 'imageId', url: 'imageUrl', alt: 'imageAlt' };
							return el( ImageField, {
								key: f.key || 'image',
								label: f.label,
								id: item[ k.id ],
								url: item[ k.url ],
								onChange: ( m ) => {
									const patch = {};
									patch[ k.id ] = m.id;
									patch[ k.url ] = m.url;
									if ( k.alt && ( m.alt || ! item[ k.alt ] ) ) {
										patch[ k.alt ] = m.alt;
									}
									update( i, patch );
								},
							} );
						}
						const common = {
							key: f.key,
							label: f.label,
							help: f.help,
							__nextHasNoMarginBottom: true,
						};
						const patchOf = ( v ) => {
							const p = {};
							p[ f.key ] = v;
							return p;
						};
						if ( f.type === 'textarea' ) {
							return el( TextareaControl, Object.assign( common, { value: item[ f.key ] || '', onChange: ( v ) => update( i, patchOf( v ) ) } ) );
						}
						if ( f.type === 'toggle' ) {
							return el( ToggleControl, Object.assign( common, { checked: !! item[ f.key ], onChange: ( v ) => update( i, patchOf( v ) ) } ) );
						}
						if ( f.type === 'select' ) {
							return el( SelectControl, Object.assign( common, { value: item[ f.key ] || '', options: f.options, onChange: ( v ) => update( i, patchOf( v ) ) } ) );
						}
						return el( TextControl, Object.assign( common, { value: item[ f.key ] || '', type: f.inputType || 'text', onChange: ( v ) => update( i, patchOf( v ) ) } ) );
					} ),
					el(
						'div',
						{ className: 'efb-repeater__actions' },
						el( Button, { icon: 'arrow-up-alt2', label: 'Move up', disabled: i === 0, onClick: () => move( i, -1 ) } ),
						el( Button, { icon: 'arrow-down-alt2', label: 'Move down', disabled: i === list.length - 1, onClick: () => move( i, 1 ) } ),
						el( Button, { icon: 'trash', label: 'Remove', isDestructive: true, onClick: () => remove( i ) } )
					)
				)
			),
			el( Button, { variant: 'primary', className: 'efb-repeater__add', onClick: () => onChange( list.concat( [ Object.assign( {}, newItem ) ] ) ) }, '+ Add ' + ( itemLabel || 'item' ).toLowerCase() )
		);
	}

	function CategorySelect( { label, value, onChange, help } ) {
		return el( ComboboxControl, {
			label,
			help,
			value: value || null,
			options: [ { value: 0, label: '— None —' } ].concat( data.categories ),
			onChange: ( v ) => onChange( parseInt( v, 10 ) || 0 ),
			__nextHasNoMarginBottom: true,
		} );
	}

	function Preview( props, name, emptyLabel, icon ) {
		const blockProps = useBlockProps();
		return el(
			'div',
			blockProps,
			el( ServerSideRender, {
				block: name,
				attributes: props.attributes,
				httpMethod: 'POST',
				EmptyResponsePlaceholder: () => el( Placeholder, { icon, label: emptyLabel, instructions: 'Use the block settings in the sidebar to add content.' } ),
			} )
		);
	}

	const dynamic = ( name, settings ) =>
		registerBlockType( name, Object.assign( { save: () => null }, settings ) );

	/* ------------------------------------------------------------------ */
	/* Section                                                             */
	/* ------------------------------------------------------------------ */
	registerBlockType( 'efb/section', {
		edit( { attributes: a, setAttributes } ) {
			const bg = a.bgImageUrl;
			const blockProps = useBlockProps( {
				className: 'efb-section efb-section--' + a.contentWidth + ( bg ? ' has-bg-image' : '' ) + ( a.overlay ? ' has-overlay' : '' ),
				style: {
					'--efb-bg': a.bgColor || undefined,
					'--efb-color': a.textColor || undefined,
					'--efb-pt': a.paddingTop + 'px',
					'--efb-pb': a.paddingBottom + 'px',
					'--efb-max': a.contentWidth === 'boxed' ? a.maxWidth + 'px' : undefined,
					'--efb-bg-image': bg ? 'url("' + bg + '")' : undefined,
					'--efb-overlay': a.overlay ? a.overlay / 100 : undefined,
					textAlign: a.textAlign || undefined,
				},
			} );
			const innerProps = useInnerBlocksProps( { className: 'efb-section__inner' }, { templateLock: false } );
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Layout' },
						el( SelectControl, {
							label: 'Content width',
							value: a.contentWidth,
							options: [
								{ value: 'boxed', label: 'Boxed' },
								{ value: 'narrow', label: 'Narrow (820px)' },
								{ value: 'full', label: 'Full width' },
							],
							onChange: ( v ) => setAttributes( { contentWidth: v } ),
						} ),
						a.contentWidth === 'boxed' ? el( RangeControl, { label: 'Max width (px)', min: 600, max: 1600, step: 10, value: a.maxWidth, onChange: ( v ) => setAttributes( { maxWidth: v } ) } ) : null,
						el( SelectControl, {
							label: 'Text alignment',
							value: a.textAlign,
							options: [
								{ value: '', label: 'Default' },
								{ value: 'left', label: 'Left' },
								{ value: 'center', label: 'Center' },
								{ value: 'right', label: 'Right' },
							],
							onChange: ( v ) => setAttributes( { textAlign: v } ),
						} ),
						el( SelectControl, {
							label: 'HTML element',
							value: a.tagName,
							options: [ 'section', 'div', 'aside', 'header', 'footer', 'article' ].map( ( t ) => ( { value: t, label: '<' + t + '>' } ) ),
							onChange: ( v ) => setAttributes( { tagName: v } ),
						} )
					),
					el(
						PanelBody,
						{ title: 'Spacing', initialOpen: false },
						el( RangeControl, { label: 'Padding top (desktop)', min: 0, max: 200, value: a.paddingTop, onChange: ( v ) => setAttributes( { paddingTop: v } ) } ),
						el( RangeControl, { label: 'Padding bottom (desktop)', min: 0, max: 200, value: a.paddingBottom, onChange: ( v ) => setAttributes( { paddingBottom: v } ) } ),
						el( RangeControl, { label: 'Padding top (mobile)', min: 0, max: 150, value: a.paddingTopMobile, onChange: ( v ) => setAttributes( { paddingTopMobile: v } ) } ),
						el( RangeControl, { label: 'Padding bottom (mobile)', min: 0, max: 150, value: a.paddingBottomMobile, onChange: ( v ) => setAttributes( { paddingBottomMobile: v } ) } )
					),
					el( PanelColorSettings, {
						title: 'Colours',
						initialOpen: false,
						colorSettings: [
							{ label: 'Background', value: a.bgColor, onChange: ( v ) => setAttributes( { bgColor: v || '' } ) },
							{ label: 'Text', value: a.textColor, onChange: ( v ) => setAttributes( { textColor: v || '' } ) },
						],
					} ),
					el(
						PanelBody,
						{ title: 'Background image', initialOpen: false },
						el( ImageField, {
							label: 'Image',
							id: a.bgImageId,
							url: a.bgImageUrl,
							onChange: ( m ) => setAttributes( { bgImageId: m.id, bgImageUrl: m.url } ),
						} ),
						bg ? el( RangeControl, { label: 'Dark overlay (%)', min: 0, max: 90, value: a.overlay, onChange: ( v ) => setAttributes( { overlay: v } ) } ) : null
					)
				),
				el( a.tagName || 'section', blockProps, el( 'div', innerProps ) )
			);
		},
		save: () => el( InnerBlocks.Content ),
	} );

	/* ---------- Toggle (show on click) ---------- */
	registerBlockType( 'efb/toggle', {
		edit( { attributes: a, setAttributes } ) {
			// Always expanded in the editor so the hidden content stays editable.
			const blockProps = useBlockProps( { className: 'efb-toggle', open: true } );
			const innerProps = useInnerBlocksProps( { className: 'efb-toggle__body' }, { templateLock: false } );
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Toggle' },
						el( ToggleControl, { label: 'Open by default', checked: a.open, onChange: ( v ) => setAttributes( { open: v } ) } )
					)
				),
				el(
					'details',
					blockProps,
					el( RichText, {
						tagName: 'summary',
						className: 'efb-toggle__title',
						value: a.title,
						allowedFormats: [],
						placeholder: 'Title shown to visitors…',
						onChange: ( v ) => setAttributes( { title: v } ),
						// Keep clicks/space in the title from collapsing the panel while typing.
						onClick: ( e ) => e.preventDefault(),
						onKeyUp: ( e ) => e.preventDefault(),
					} ),
					el( 'div', innerProps )
				)
			);
		},
		save: () => el( InnerBlocks.Content ),
	} );

	/* ------------------------------------------------------------------ */
	/* Hero slider                                                         */
	/* ------------------------------------------------------------------ */
	dynamic( 'efb/hero-slider', {
		edit( props ) {
			const { attributes: a, setAttributes } = props;
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Slider settings' },
						el( ToggleControl, { label: 'Autoplay', checked: a.autoplay, onChange: ( v ) => setAttributes( { autoplay: v } ) } ),
						a.autoplay ? el( RangeControl, { label: 'Interval (ms)', min: 2000, max: 15000, step: 500, value: a.interval, onChange: ( v ) => setAttributes( { interval: v } ) } ) : null,
						el( RangeControl, { label: 'Minimum height (px)', min: 240, max: 800, step: 10, value: a.minHeight, onChange: ( v ) => setAttributes( { minHeight: v } ) } )
					),
					el(
						PanelBody,
						{ title: 'Slides' },
						el( Repeater, {
							items: a.slides,
							onChange: ( v ) => setAttributes( { slides: v } ),
							titleKey: 'title',
							itemLabel: 'Slide',
							newItem: { eyebrow: '', title: 'New slide', text: '', buttonText: 'Shop now', buttonUrl: '', imageId: 0, imageUrl: '', imageAlt: '' },
							fields: [
								{ key: 'eyebrow', label: 'Eyebrow (small label)' },
								{ key: 'title', label: 'Title' },
								{ key: 'text', label: 'Text', type: 'textarea' },
								{ key: 'buttonText', label: 'Button text' },
								{ key: 'buttonUrl', label: 'Button link', help: 'Relative links (e.g. /range/display-cabinets/) survive domain changes.' },
								{ type: 'image', label: 'Image' },
								{ key: 'imageAlt', label: 'Image alt text' },
							],
						} )
					)
				),
				Preview( props, 'efb/hero-slider', 'Hero Slider', 'slides' )
			);
		},
	} );

	/* ------------------------------------------------------------------ */
	/* Offer cards                                                         */
	/* ------------------------------------------------------------------ */
	dynamic( 'efb/offer-cards', {
		edit( props ) {
			const { attributes: a, setAttributes } = props;
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Layout' },
						el( RangeControl, { label: 'Columns', min: 1, max: 6, value: a.columns, onChange: ( v ) => setAttributes( { columns: v } ) } )
					),
					el( PanelColorSettings, {
						title: 'Colours',
						initialOpen: false,
						colorSettings: [ { label: 'Offer label', value: a.labelColor, onChange: ( v ) => setAttributes( { labelColor: v || '#ff0000' } ) } ],
					} ),
					el(
						PanelBody,
						{ title: 'Offers' },
						el( Repeater, {
							items: a.items,
							onChange: ( v ) => setAttributes( { items: v } ),
							titleKey: 'subtitle',
							itemLabel: 'Offer',
							newItem: { label: 'Special Offer £0 +VAT', title: '', subtitle: '', url: '', imageId: 0, imageUrl: '', imageAlt: '' },
							fields: [
								{ key: 'label', label: 'Offer label (e.g. Special Offer £698 +VAT)' },
								{ type: 'image', label: 'Image' },
								{ key: 'imageAlt', label: 'Image alt text' },
								{ key: 'title', label: 'Title (e.g. Patisserie Display)' },
								{ key: 'subtitle', label: 'Model (e.g. ECO-P60/90)' },
								{ key: 'url', label: 'Link' },
							],
						} )
					)
				),
				Preview( props, 'efb/offer-cards', 'Offer Cards', 'tag' )
			);
		},
	} );

	/* ------------------------------------------------------------------ */
	/* Logo grid                                                           */
	/* ------------------------------------------------------------------ */
	dynamic( 'efb/logo-grid', {
		edit( props ) {
			const { attributes: a, setAttributes } = props;
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Layout' },
						el( TextControl, { label: 'Heading (optional)', value: a.heading, onChange: ( v ) => setAttributes( { heading: v } ) } ),
						el( RangeControl, { label: 'Columns (desktop)', min: 2, max: 12, value: a.columns, onChange: ( v ) => setAttributes( { columns: v } ) } ),
						el( ToggleControl, { label: 'Greyscale until hover', checked: a.grayscale, onChange: ( v ) => setAttributes( { grayscale: v } ) } ),
						el( ToggleControl, { label: 'Swipeable strip on phones', checked: a.mobileCarousel, onChange: ( v ) => setAttributes( { mobileCarousel: v } ) } )
					),
					el(
						PanelBody,
						{ title: 'Logos' },
						el(
							MediaUploadCheck,
							null,
							el( MediaUpload, {
								allowedTypes: [ 'image' ],
								multiple: true,
								gallery: false,
								onSelect: ( media ) =>
									setAttributes( {
										logos: ( a.logos || [] ).concat(
											media.map( ( m ) => ( { id: m.id, url: m.url, alt: m.alt || m.title || '', link: '' } ) )
										),
									} ),
								render: ( { open } ) => el( Button, { variant: 'secondary', onClick: open, style: { marginBottom: '12px' } }, 'Add logos from media library' ),
							} )
						),
						el( Repeater, {
							items: a.logos,
							onChange: ( v ) => setAttributes( { logos: v } ),
							titleKey: 'alt',
							itemLabel: 'Logo',
							newItem: { id: 0, url: '', alt: '', link: '' },
							fields: [
								{ type: 'image', label: 'Logo', keys: { id: 'id', url: 'url', alt: 'alt' } },
								{ key: 'alt', label: 'Company name (alt text)' },
								{ key: 'link', label: 'Link (optional)' },
							],
						} )
					)
				),
				Preview( props, 'efb/logo-grid', 'Logo Grid', 'awards' )
			);
		},
	} );

	/* ------------------------------------------------------------------ */
	/* Category tiles                                                      */
	/* ------------------------------------------------------------------ */
	dynamic( 'efb/category-tiles', {
		edit( props ) {
			const { attributes: a, setAttributes } = props;
			const byLabel = {};
			const byId = {};
			data.categories.forEach( ( c ) => {
				byLabel[ c.label ] = c.value;
				byId[ c.value ] = c.label;
			} );
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Categories' },
						el( TextControl, { label: 'Heading (optional)', value: a.heading, onChange: ( v ) => setAttributes( { heading: v } ) } ),
						el( CategorySelect, {
							label: 'Show sub-categories of',
							help: 'Order follows the “order” field of each category.',
							value: a.parent,
							onChange: ( v ) => setAttributes( { parent: v } ),
						} ),
						el( FormTokenField, {
							label: '…or pick categories manually',
							value: ( a.include || [] ).map( ( id ) => byId[ id ] ).filter( Boolean ),
							suggestions: data.categories.map( ( c ) => c.label ),
							onChange: ( labels ) => setAttributes( { include: labels.map( ( l ) => byLabel[ l ] ).filter( Boolean ) } ),
							__experimentalExpandOnFocus: true,
						} ),
						el( RangeControl, { label: 'Columns', min: 1, max: 8, value: a.columns, onChange: ( v ) => setAttributes( { columns: v } ) } ),
						el( ToggleControl, { label: 'Hide empty categories', checked: a.hideEmpty, onChange: ( v ) => setAttributes( { hideEmpty: v } ) } ),
						el( ToggleControl, { label: 'Link the heading to the parent category', checked: a.headingLink, onChange: ( v ) => setAttributes( { headingLink: v } ) } ),
						el( SelectControl, {
							label: 'Name colour',
							value: a.style,
							options: [
								{ value: 'plain', label: 'Brand green' },
								{ value: 'dark', label: 'Black' },
							],
							onChange: ( v ) => setAttributes( { style: v } ),
						} )
					),
					el(
						PanelBody,
						{ title: 'Extra tiles (any link)', initialOpen: false },
						el( Repeater, {
							items: a.items,
							onChange: ( v ) => setAttributes( { items: v } ),
							titleKey: 'title',
							itemLabel: 'Tile',
							newItem: { title: '', url: '', imageId: 0, imageUrl: '' },
							fields: [
								{ key: 'title', label: 'Title' },
								{ key: 'url', label: 'Link' },
								{ type: 'image', label: 'Image', keys: { id: 'imageId', url: 'imageUrl' } },
							],
						} )
					)
				),
				Preview( props, 'efb/category-tiles', 'Category Tiles', 'grid-view' )
			);
		},
	} );

	/* ------------------------------------------------------------------ */
	/* Product grid                                                        */
	/* ------------------------------------------------------------------ */
	dynamic( 'efb/product-grid', {
		edit( props ) {
			const { attributes: a, setAttributes } = props;
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Products' },
						el( CategorySelect, { label: 'Category', value: a.category, onChange: ( v ) => setAttributes( { category: v } ) } ),
						el( SelectControl, {
							label: 'Market',
							value: a.market || 0,
							options: [ { value: 0, label: '— Any —' } ].concat( data.markets || [] ),
							onChange: ( v ) => setAttributes( { market: parseInt( v, 10 ) || 0 } ),
						} ),
						el( TextControl, {
							label: 'Or specific product IDs (comma separated)',
							value: ( a.ids || [] ).join( ',' ),
							onChange: ( v ) => setAttributes( { ids: v.split( ',' ).map( ( s ) => parseInt( s, 10 ) ).filter( Boolean ) } ),
						} ),
						el( RangeControl, { label: 'Number of products', min: 1, max: 48, value: a.count, onChange: ( v ) => setAttributes( { count: v } ) } ),
						el( RangeControl, { label: 'Columns', min: 1, max: 6, value: a.columns, onChange: ( v ) => setAttributes( { columns: v } ) } ),
						el( SelectControl, {
							label: 'Order by',
							value: a.orderby,
							options: [
								{ value: 'menu_order', label: 'Custom order' },
								{ value: 'date', label: 'Newest' },
								{ value: 'title', label: 'Title' },
								{ value: 'price', label: 'Price (low to high)' },
								{ value: 'rand', label: 'Random' },
							],
							onChange: ( v ) => setAttributes( { orderby: v } ),
						} ),
						el( ToggleControl, { label: 'Show “From £X +VAT”', checked: a.showPrice, onChange: ( v ) => setAttributes( { showPrice: v } ) } ),
						el( ToggleControl, { label: 'Show button', checked: a.showButton, onChange: ( v ) => setAttributes( { showButton: v } ) } )
					)
				),
				Preview( props, 'efb/product-grid', 'Product Grid', 'products' )
			);
		},
	} );

	/* ------------------------------------------------------------------ */
	/* Product search                                                      */
	/* ------------------------------------------------------------------ */
	dynamic( 'efb/product-search', {
		edit( props ) {
			const { attributes: a, setAttributes } = props;
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Search' },
						el( TextControl, { label: 'Placeholder', value: a.placeholder, onChange: ( v ) => setAttributes( { placeholder: v } ) } ),
						el( ToggleControl, { label: 'Show button', checked: a.showButton, onChange: ( v ) => setAttributes( { showButton: v } ) } ),
						a.showButton ? el( TextControl, { label: 'Button text', value: a.buttonText, onChange: ( v ) => setAttributes( { buttonText: v } ) } ) : null
					)
				),
				Preview( props, 'efb/product-search', 'Product Search', 'search' )
			);
		},
	} );

	/* ------------------------------------------------------------------ */
	/* Contact form                                                        */
	/* ------------------------------------------------------------------ */
	const defaultFields = [
		{ name: 'name', label: 'Name', type: 'text', required: true },
		{ name: 'company', label: 'Company', type: 'text', required: false },
		{ name: 'email', label: 'Email', type: 'email', required: true },
		{ name: 'phone', label: 'Phone', type: 'tel', required: true },
		{ name: 'enquiry', label: 'Enquiry', type: 'textarea', required: false },
	];

	dynamic( 'efb/contact-form', {
		edit( props ) {
			const { attributes: a, setAttributes, clientId } = props;
			useEffect( () => {
				const patch = {};
				if ( ! a.formId ) {
					patch.formId = 'f' + clientId.replace( /[^a-z0-9]/gi, '' ).slice( 0, 10 ).toLowerCase();
				}
				if ( ! a.fields || ! a.fields.length ) {
					patch.fields = defaultFields;
				}
				if ( Object.keys( patch ).length ) {
					setAttributes( patch );
				}
			}, [] );
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Notifications' },
						el( TextControl, { label: 'Form name (shown in Form Entries)', value: a.formName, onChange: ( v ) => setAttributes( { formName: v } ) } ),
						el( TextControl, { label: 'Send entries to (email)', type: 'email', value: a.recipient, onChange: ( v ) => setAttributes( { recipient: v } ) } ),
						el( TextControl, { label: 'Email subject', value: a.subject, onChange: ( v ) => setAttributes( { subject: v } ) } ),
						el( TextareaControl, { label: 'Success message', value: a.successMessage, onChange: ( v ) => setAttributes( { successMessage: v } ) } ),
						el( TextControl, { label: 'Button text', value: a.buttonText, onChange: ( v ) => setAttributes( { buttonText: v } ) } )
					),
					el(
						PanelBody,
						{ title: 'Fields', initialOpen: false },
						el( Repeater, {
							items: a.fields,
							onChange: ( v ) => setAttributes( { fields: v } ),
							titleKey: 'label',
							itemLabel: 'Field',
							newItem: { name: 'field_' + Date.now().toString( 36 ), label: 'New field', type: 'text', required: false },
							fields: [
								{ key: 'label', label: 'Label' },
								{ key: 'name', label: 'Field key (letters, no spaces)' },
								{
									key: 'type',
									label: 'Type',
									type: 'select',
									options: [
										{ value: 'text', label: 'Text' },
										{ value: 'email', label: 'Email' },
										{ value: 'tel', label: 'Phone' },
										{ value: 'number', label: 'Number' },
										{ value: 'url', label: 'Website' },
										{ value: 'textarea', label: 'Paragraph' },
									],
								},
								{ key: 'required', label: 'Required', type: 'toggle' },
							],
						} )
					)
				),
				Preview( props, 'efb/contact-form', 'Contact Form', 'email' )
			);
		},
	} );

	/* ------------------------------------------------------------------ */
	/* Spec table (edited in place)                                        */
	/* ------------------------------------------------------------------ */
	dynamic( 'efb/spec-table', {
		edit( { attributes: a, setAttributes, isSelected } ) {
			const head = a.head || [];
			const rows = a.rows || [];
			const cols = Math.max( head.length, rows.reduce( ( m, r ) => Math.max( m, r.length ), 0 ), 1 );
			const pad = ( r ) => {
				const out = r.slice();
				while ( out.length < cols ) {
					out.push( '' );
				}
				return out;
			};
			const setCell = ( ri, ci, v ) => setAttributes( { rows: rows.map( ( r, i ) => ( i === ri ? pad( r ).map( ( c, j ) => ( j === ci ? v : c ) ) : r ) ) } );
			const setHead = ( ci, v ) => setAttributes( { head: pad( head ).map( ( c, j ) => ( j === ci ? v : c ) ) } );
			const addRow = () => setAttributes( { rows: rows.concat( [ pad( [] ) ] ) } );
			const addCol = () => setAttributes( { head: pad( head ).concat( [ 'Column' ] ), rows: rows.map( ( r ) => pad( r ).concat( [ '' ] ) ) } );
			const delRow = ( ri ) => setAttributes( { rows: rows.filter( ( _, i ) => i !== ri ) } );
			const delCol = ( ci ) => setAttributes( { head: pad( head ).filter( ( _, j ) => j !== ci ), rows: rows.map( ( r ) => pad( r ).filter( ( _, j ) => j !== ci ) ) } );
			const blockProps = useBlockProps( { className: 'efb-table efb-table--editor' } );

			return el(
				Fragment,
				null,
				el(
					BlockControls,
					null,
					el(
						ToolbarGroup,
						null,
						el( ToolbarButton, { icon: 'table-row-after', label: 'Add row', onClick: addRow } ),
						el( ToolbarButton, { icon: 'table-col-after', label: 'Add column', onClick: addCol } )
					)
				),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Table' },
						el( TextControl, { label: 'Caption (optional)', value: a.caption, onChange: ( v ) => setAttributes( { caption: v } ) } ),
						el( ToggleControl, { label: 'Sortable columns', checked: a.sortable, onChange: ( v ) => setAttributes( { sortable: v } ) } ),
						el( Notice, { status: 'info', isDismissible: false }, 'On phones the table is shown as stacked cards using the header text as labels.' )
					)
				),
				el(
					'div',
					blockProps,
					el(
						'table',
						null,
						el(
							'thead',
							null,
							el(
								'tr',
								null,
								pad( head ).map( ( h, ci ) =>
									el(
										'th',
										{ key: ci },
										el( RichText, { tagName: 'span', value: h, allowedFormats: [ 'core/bold' ], placeholder: 'Header', onChange: ( v ) => setHead( ci, v ) } ),
										isSelected ? el( Button, { className: 'efb-table__del', icon: 'no-alt', label: 'Delete column', size: 'small', onClick: () => delCol( ci ) } ) : null
									)
								),
								isSelected ? el( 'th', { className: 'efb-table__tools' } ) : null
							)
						),
						el(
							'tbody',
							null,
							rows.map( ( r, ri ) =>
								el(
									'tr',
									{ key: ri },
									pad( r ).map( ( c, ci ) =>
										el( 'td', { key: ci }, el( RichText, { tagName: 'span', value: c, allowedFormats: [ 'core/bold', 'core/link', 'core/italic' ], placeholder: '—', onChange: ( v ) => setCell( ri, ci, v ) } ) )
									),
									isSelected ? el( 'td', { className: 'efb-table__tools' }, el( Button, { icon: 'trash', label: 'Delete row', size: 'small', isDestructive: true, onClick: () => delRow( ri ) } ) ) : null
								)
							)
						)
					),
					isSelected
						? el(
								'div',
								{ className: 'efb-table__add' },
								el( Button, { variant: 'secondary', onClick: addRow }, '+ Row' ),
								el( Button, { variant: 'secondary', onClick: addCol }, '+ Column' )
						  )
						: null
				)
			);
		},
	} );

	/* ------------------------------------------------------------------ */
	/* CTA banner                                                          */
	/* ------------------------------------------------------------------ */
	dynamic( 'efb/cta-banner', {
		edit( props ) {
			const { attributes: a, setAttributes } = props;
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Content' },
						el( TextControl, { label: 'Text', value: a.text, onChange: ( v ) => setAttributes( { text: v } ) } ),
						el( TextControl, { label: 'Phone', value: a.phone, onChange: ( v ) => setAttributes( { phone: v } ) } ),
						el( TextControl, { label: 'Button text (optional)', value: a.buttonText, onChange: ( v ) => setAttributes( { buttonText: v } ) } ),
						el( TextControl, { label: 'Button link', value: a.buttonUrl, onChange: ( v ) => setAttributes( { buttonUrl: v } ) } )
					),
					el( PanelColorSettings, {
						title: 'Colours',
						initialOpen: false,
						colorSettings: [ { label: 'Background', value: a.bgColor, onChange: ( v ) => setAttributes( { bgColor: v || '#1f1f1f' } ) } ],
					} ),
					el(
						PanelBody,
						{ title: 'Background image', initialOpen: false },
						el( ImageField, { label: 'Image', id: a.bgImageId, url: a.bgImageUrl, onChange: ( m ) => setAttributes( { bgImageId: m.id, bgImageUrl: m.url } ) } )
					)
				),
				Preview( props, 'efb/cta-banner', 'Call-to-action Banner', 'megaphone' )
			);
		},
	} );

	/* ------------------------------------------------------------------ */
	/* Post cards                                                          */
	/* ------------------------------------------------------------------ */
	dynamic( 'efb/post-cards', {
		edit( props ) {
			const { attributes: a, setAttributes } = props;
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Cards' },
						el( SelectControl, {
							label: 'Content',
							value: a.postType,
							options: [
								{ value: 'post', label: 'Blog posts' },
								{ value: 'brochure', label: 'Brochures' },
								{ value: 'video', label: 'Videos' },
							],
							onChange: ( v ) => setAttributes( { postType: v } ),
						} ),
						el( RangeControl, { label: 'Number of items', min: 1, max: 24, value: a.count, onChange: ( v ) => setAttributes( { count: v } ) } ),
						el( RangeControl, { label: 'Columns', min: 1, max: 4, value: a.columns, onChange: ( v ) => setAttributes( { columns: v } ) } ),
						el( SelectControl, {
							label: 'Order',
							value: a.order,
							options: [
								{ value: 'desc', label: 'Newest first' },
								{ value: 'asc', label: 'Oldest first' },
							],
							onChange: ( v ) => setAttributes( { order: v } ),
						} ),
						el( TextControl, {
							label: 'Or specific IDs, in this order (comma separated)',
							value: ( a.ids || [] ).join( ',' ),
							onChange: ( v ) => setAttributes( { ids: v.split( ',' ).map( ( s ) => parseInt( s, 10 ) ).filter( Boolean ) } ),
						} ),
						a.postType === 'brochure' ? el( ToggleControl, { label: 'Link straight to the PDF', checked: a.linkToFile, onChange: ( v ) => setAttributes( { linkToFile: v } ) } ) : null,
						a.postType === 'video' ? el( ToggleControl, { label: 'Embed the YouTube player', checked: a.embedVideo, onChange: ( v ) => setAttributes( { embedVideo: v } ) } ) : null,
						el( ToggleControl, { label: 'Show excerpt', checked: a.showExcerpt, onChange: ( v ) => setAttributes( { showExcerpt: v } ) } ),
						el( ToggleControl, { label: 'Show date', checked: a.showDate, onChange: ( v ) => setAttributes( { showDate: v } ) } )
					)
				),
				Preview( props, 'efb/post-cards', 'Post Cards', 'excerpt-view' )
			);
		},
	} );
} )( window.wp );
