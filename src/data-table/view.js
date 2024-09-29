/**
 * WordPress dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

let initTable = ( el ) => {
	const title = document.querySelector( '.site-main h1' )?.innerText || document.title;
	return new DataTable( el, {
		info: false,
		pageLength: 50,
		layout: {
			bottomEnd: {
				paging: {
					type: 'simple_numbers',
				},
			},
			topEnd: {
				search: {
					placeholder: 'Enter keyword...',
					text: state.searchLabel,
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
		}
	} );
}

const { state, actions, callbacks } = store( 'ttft/data-tables', {
	state: {
		isLoading: false
	},
    actions: {
        async renderTable() {
            try {
                const { tableType, thinkTank, donor, donationYear, donorType, ajaxUrl, action, nonce } = state;

                const formData = new FormData();
                formData.append( 'action', action );
                formData.append( 'nonce', nonce );
                formData.append( 'table_type', tableType );
                formData.append( 'search_label', searchLabel || 'Enter keyword...' );
                formData.append( 'donation_year', donationYear || 'all' );
                formData.append( 'donor_type', donorType || 'all' );
				formData.append( 'think_tank', thinkTank );
                formData.append( 'donor', donor );

                callbacks.logFormData( formData );

				state.isLoading = true;

                const response = await fetch( ajaxUrl, {
                    method: 'POST',
                    body: formData,
                });

                const responseText = await response.text();

                callbacks.logResponseData( responseText );

				if ( ! response.ok ) {
                    throw new Error( `HTTP error! status: ${response.status}` );
                }

                const jsonResponse = JSON.parse( responseText );

                if ( jsonResponse.success ) {
                    state.tableData = jsonResponse.data;
                    // console.log( 'renderTable', JSON.stringify( state.tableData, undefined, 2 ) );

					actions.destroyTable();

					const container = document.getElementById( state.elementId );
                    container.innerHTML = ''; 

                    container.innerHTML = jsonResponse.data;
					state.table = initTable( `#${state.tableId}` );

                } else {
                    console.error( 'Error fetching table data:', jsonResponse.data );
                }
            } catch ( event ) {
                console.error( 'Something went wrong!', event );
            } finally {
                state.isLoading = false;
            }
        },
		initTable: () => {
			state.table = initTable( `#${state.tableId}` );
		},
		destroyTable: () => {
			state.table.clear().draw();
			state.table.destroy();
		},
        updateContext: () => {
            const context = getContext();
            context.tableType = state.tableType;
            context.donationYear = state.donationYear;
            context.donorType = state.donorType;
        }
    },
    callbacks: {
        initLog: () => {
            console.log( `Initial State: `, JSON.stringify( state, undefined, 2 )  );
            // const context = getContext();
            const { tableType, donationYear, donorType } = getContext();
            console.log( `Initial Context: ${tableType}, ${donationYear}, ${donorType}` );
        },
        logTable: () => {
            console.log( `State: `, JSON.stringify( state, undefined, 2 )  );
            // const context = getContext();
            const { tableType, donationYear, donorType } = getContext();
            console.log( `Context: ${tableType}, ${donationYear}, ${donorType}` );
        },
        logFormData: ( data ) => {
            for ( const [ key, value ] of data.entries() ) {
                console.log( `${ key }: ${ value }` );
            }
        },
        logResponseData: ( data ) => {
            // console.log( 'Raw response:', data );
        },
		logLoading: () => {
			console.log( `IS LOADING ${state.isLoading}` );
		}
    },
});
