/**
 * WordPress dependencies
 */
import {
	store,
	getContext,
	getElement,
	useState,
	useEffect,
} from '@wordpress/interactivity';

const initTable = ( table ) => {
	const title = document.querySelector( '.site-main h1' )?.innerText || document.title;
	const pageLength = state.pageLength;
	return new DataTable( table, {
		pageLength: 25,
		retrieve: true,
		api: true,
		layout: {
			bottomStart: {
				info: {
					callback: function ( s, start, end, max, total, result ) {
						return ``;
					},
				},
			},
			bottomEnd: {
				paging: {
					type: 'simple_numbers',
				},
				info: {
					callback: function ( s, start, end, max, total, result ) {
						return `${ max } Records Found.`;
					},
				},
			},
			topEnd: {
				search: {
					placeholder: 'Enter keyword...',
					text: state.searchLabel || 'Search',
				},
			},
			topStart: {
				buttons: [
					{
						extend: 'csvHtml5',
						title: title,
						text: 'Download Data',
					},
				],
			},
		},

		footerCallback: function ( row, data, start, end, display ) {},
	} );
};

const hasStateChanged = () => {
    const [ type, setType ] = useState( state.donorType );
    const [ year, setYear ] = useState( state.donationYear );

    const checkStateChanged = () => {
        return type !== state.donorType || year !== state.donationYear;
    };

    useEffect(() => {
        if ( checkStateChanged() ) {
            setType( state.donorType );
            setYear( state.donationYear );
        }
    }, [ state.donorType, state.donationYear ] );

    return checkStateChanged();
};

const { state, actions, callbacks } = store( 'ttft/data-tables', {
	state: {
		isLoading: false,
		pageLength: 50,
		donationYear: 'all',
		donorType: 'all',
		entityType: 'think_tank',
	},
	actions: {
		async renderTable() {
			const context = getContext();

			try {
				const {
					tableType,
					thinkTank,
					donor,
					donationYear,
					donorType,
					search,
					searchLabel,
					ajaxUrl,
					action,
					nonce,
				} = state;

				const formData = new FormData();
				formData.append( 'action', action );
				formData.append( 'nonce', nonce );
				formData.append( 'table_type', tableType );
				formData.append( 'search_label', searchLabel || 'Search' );
				formData.append( 'donation_year', donationYear || 'all' );
				formData.append( 'donor_type', donorType || 'all' );
				formData.append( 'think_tank', thinkTank );
				formData.append( 'donor', donor );
				formData.append( 'search', search || '' );

				console.log( `formData: `, formData );

				state.isLoading = true;
				context.isLoaded = false;

				const response = await fetch( ajaxUrl, {
					method: 'POST',
					body: formData,
				} );

				const responseText = await response.text();

				console.log( `responseText: `, responseText );

				if ( ! response.ok ) {
					throw new Error(
						`HTTP error! status: ${ response.status }`
					);
				}

				const jsonResponse = JSON.parse( responseText );

				if ( jsonResponse.success ) {
					state.tableData = jsonResponse.data;

					// debugger;

					await actions.destroyTableAsync();

					const container = document.getElementById(
						state.elementId
					);

					if ( container ) {
						container.innerHTML = jsonResponse.data;

						if ( state.table ) {
							state.table = initTable( `#${ state.tableId }` );
						}
					}
				} else {
					console.error( `Error fetching table data:`, jsonResponse.data );
				}
			} catch ( event ) {
				console.error( `catch( event ) renderTable:`, event );
			} finally {
				state.isLoading = false;
				context.isLoaded = true;
			}
		},
		initTable: () => {
			state.table = initTable( `#${state.tableId}` );
		},
		destroyTable: () => {
			state.table.clear().draw();
			state.table.destroy();
		},
		destroyTableAsync: () => {
			return new Promise( ( resolve ) => {
				actions.destroyTable();
				resolve();
			} );
		},
		setContext: () => {
			const context = getContext();
			// context.tableType && ( context.tableType = state.tableType );
			// context.donationYear && ( context.donationYear = state.donationYear );
			// context.donorType && ( context.donorType = state.donorType );
			context.searchLabel && ( context.searchLabel = state.searchLabel );
		},
		generateSkeletonTable: ( columns ) => {
			let rows = '';
			const max = 10;
			for ( let rowIndex = 0; rowIndex < max; rowIndex++ ) {
				rows += '<tr class="row skeleton-row" width="100%">';
				for ( let colIndex = 0; colIndex < columns; colIndex++ ) {
					rows +=
						'<td class="cell skeleton-cell"><div class="loader"></div></td>';
				}
				rows += '</tr>';
			}
			return rows;
		},
	},
	callbacks: {
		renderTable: () => {
			const hasChanged = hasStateChanged();
			useEffect( () => {
				if ( hasChanged ) {
					actions.renderTable();
				}
			} );
		},
		loadAnimation: () => {
			useEffect( () => {
				const container = document.getElementById( state.elementId );
				if ( state.isLoading && container ) {
					const columns = container.querySelectorAll( 'thead th' ).length;
					const skeletonTable = actions.generateSkeletonTable( columns );
					const body = container.querySelector( 'tbody' );
					if ( body ) {
						body.innerHTML = skeletonTable;
					}
				}
			}, [ state.isLoading ] );
		},
		initLog: () => {
			// console.log( `Initial State: `, JSON.stringify( state, undefined, 2 )  );
			// const { tableType, donationYear, donorType, isLoaded } = getContext();
			// console.log( `Initial Context: ${tableType}, isLoaded: ${isLoaded}, ${donationYear}, ${donorType}` );
		},
		logTable: () => {
			const { tableType, thinkTank, donor, donationYear, donorType, search, isLoading } = state;
			console.log( `State: `, tableType, thinkTank, donor, donationYear, donorType, isLoading );
		},
		logState: ( key ) => {
			console.log( `key: `, state.key );
		},
		logLoading: () => {
			const context = getContext();
			console.log( `IS LOADING: `, state.isLoading, context.isLoaded );
		},
	},
} );
