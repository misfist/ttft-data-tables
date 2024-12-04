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
	__experimentalNumberControl as NumberControl,
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
	attributes: { tableType, thinkTank, donor, donorType, donationYear, number },
	setAttributes,
} ) {
	const blockProps = useBlockProps();

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

	if (
		! donorTypeTerms ||
		! donationYearTerms
	) {
		return <Spinner />;
	}

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Table Settings', 'data-table' ) }>
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
					<NumberControl
						label={ __( 'Number', 'data-table' ) }
						max={ 20 }
						min={ 1 }
						value={ number || 10 }
						onChange={ ( value ) =>
							setAttributes( { number: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
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
							<tr>
								<td>{ __( 'Number', 'data-table' ) }</td>
								<td>
									{ number || 10 }
								</td>
							</tr>
						</tbody>
					</table>
				</figure>
			</div>
		</div>
	);
}
