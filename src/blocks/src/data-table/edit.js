/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the className name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	Dashicon,
	SelectControl,
	PanelBody,
	Spinner,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { useState } from '@wordpress/element';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @param {Object}   props               Properties passed to the function.
 * @param {Object}   props.attributes    Available block attributes.
 * @param {Function} props.setAttributes Function that updates individual attributes.
 *
 * @return {Element} Element to render.
 */
export default function Edit( {
	attributes: { tableType, thinkTank, donor, donorType, donationYear },
	setAttributes,
} ) {
	const blockProps = useBlockProps();
	const [ tableTypeState, setTableTypeState ] = useState( tableType );

	const donorTerms = useSelect(
		( select ) =>
			select( coreStore ).getEntityRecords( 'taxonomy', 'donor', {
				per_page: -1,
				orderby: 'name',
			} ),
		[]
	);
	const thinkTankTerms = useSelect(
		( select ) =>
			select( coreStore ).getEntityRecords( 'taxonomy', 'think_tank', {
				per_page: -1,
				orderby: 'name',
			} ),
		[]
	);
	const donorTypeTerms = useSelect(
		( select ) =>
			select( coreStore ).getEntityRecords( 'taxonomy', 'donor_type', {
				per_page: -1,
				orderby: 'name',
			} ),
		[]
	);
	const donationYearTerms = useSelect(
		( select ) =>
			select( coreStore ).getEntityRecords( 'taxonomy', 'donation_year', {
				per_page: -1,
				orderby: 'name',
				order: 'desc',
			} ),
		[]
	);

	const tableTypeOptions = [
		{ label: __( 'Donors', 'data-table' ), value: 'donor-archive' },
		{ label: __( 'Single Donor', 'data-table' ), value: 'single-donor' },
		{
			label: __( 'Think Tanks', 'data-table' ),
			value: 'think-tank-archive',
		},
		{
			label: __( 'Single Think Tank', 'data-table' ),
			value: 'single-think-tank',
		},
	];

	const donorOptions = donorTerms
		? donorTerms.map( ( term ) => ( {
				label: term.name,
				value: term.slug,
		  } ) )
		: [];
	const thinkTankOptions = thinkTankTerms
		? thinkTankTerms.map( ( term ) => ( {
				label: term.name,
				value: term.slug,
		  } ) )
		: [];
	const donorTypeOptions = donorTypeTerms
		? [
				{ label: 'All', value: 'all' },
				...donorTypeTerms.map( ( term ) => ( {
					label: term.name,
					value: term.slug,
				} ) ),
		  ]
		: [];
	const donationYearOptions = donationYearTerms
		? [
				{ label: 'All', value: 'all' },
				...donationYearTerms.map( ( term ) => ( {
					label: term.name,
					value: term.slug,
				} ) ),
		  ]
		: [];

	const isSingleDonor = ( type ) => type === 'single-donor';
	const isSingleThinkTank = ( type ) => type === 'single-think-tank';

	if (
		! donorTerms ||
		! thinkTankTerms ||
		! donorTypeTerms ||
		! donationYearTerms
	) {
		return <Spinner />;
	}

	const missingSelectionsMessage =
		( isSingleDonor( tableTypeState ) && ! donor ) ||
		( isSingleThinkTank( tableTypeState ) && ! thinkTank );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Table Settings', 'data-table' ) }>
					<SelectControl
						label={ __( 'Table Type', 'data-table' ) }
						value={ tableTypeState }
						options={ tableTypeOptions }
						onChange={ ( value ) => {
							setTableTypeState( value );
							setAttributes( { tableType: value } );
						} }
					/>
					{ isSingleDonor( tableTypeState ) && (
						<SelectControl
							label={ __( 'Donor', 'data-table' ) }
							value={ donor }
							options={ donorOptions }
							onChange={ ( value ) =>
								setAttributes( { donor: value } )
							}
						/>
					) }
					{ isSingleThinkTank( tableTypeState ) && (
						<SelectControl
							label={ __( 'Think Tank', 'data-table' ) }
							value={ thinkTank }
							options={ thinkTankOptions }
							onChange={ ( value ) =>
								setAttributes( { thinkTank: value } )
							}
						/>
					) }
					<SelectControl
						label={ __( 'Donor Type', 'data-table' ) }
						value={ donorType }
						options={ donorTypeOptions }
						onChange={ ( value ) =>
							setAttributes( { donorType: value } )
						}
					/>
					<SelectControl
						label={ __( 'Donation Year', 'data-table' ) }
						value={ donationYear }
						options={ donationYearOptions }
						onChange={ ( value ) =>
							setAttributes( { donationYear: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			{ missingSelectionsMessage && (
				<span
					style={ {
						color: 'var(--wp--preset--color--vivid-red)',
						fontWeight: 'bold',
					} }
				>
					{ __( 'Required selections are missing', 'data-table' ) }
				</span>
			) }
			<div>
				<figure className="wp-block-table">
					<figcaption className="wp-element-caption">
						<Dashicon icon="editor-table" />{ ' ' }
						{ __( 'Selected Attributes', 'data-table' ) }
					</figcaption>
					<table className="dataTable">
						<thead>
							<tr>
								<th>{ __( 'Attribute', 'data-table' ) }</th>
								<th>{ __( 'Value', 'data-table' ) }</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>{ __( 'Table Type', 'data-table' ) }</td>
								<td>
									{ tableTypeOptions.find(
										( option ) =>
											option.value === tableTypeState
									)?.label || tableTypeState }
								</td>
							</tr>
							{ isSingleDonor( tableTypeState ) && (
								<tr>
									<td>{ __( 'Donor', 'data-table' ) }</td>
									<td>
										{ donorTerms.find(
											( term ) => term.slug === donor
										)?.name || missingSelectionsMessage }
									</td>
								</tr>
							) }
							{ isSingleThinkTank( tableTypeState ) && (
								<tr>
									<td>
										{ __( 'Think Tank', 'data-table' ) }
									</td>
									<td>
										{ thinkTankTerms.find(
											( term ) => term.slug === thinkTank
										)?.name || missingSelectionsMessage }
									</td>
								</tr>
							) }
							<tr>
								<td>{ __( 'Donor Type', 'data-table' ) }</td>
								<td>
									{ donorTypeTerms.find(
										( term ) => term.slug === donorType
									)?.name || __( 'All', 'data-table' ) }
								</td>
							</tr>
							<tr>
								<td>{ __( 'Donation Year', 'data-table' ) }</td>
								<td>
									{ donationYearTerms.find(
										( term ) => term.slug === donationYear
									)?.name || __( 'All', 'data-table' ) }
								</td>
							</tr>
						</tbody>
					</table>
				</figure>
			</div>
		</div>
	);
}
