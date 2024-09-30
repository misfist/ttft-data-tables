/**
 * WordPress dependencies
 */
import { store, getContext, getElement, useState, useEffect } from '@wordpress/interactivity';

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

const skeletonTable = `
    <table class="dataTable">
        <thead>
            <tr>
                <th>Loading...</th>
                <th>Loading...</th>
                <th>Loading...</th>
                <th>Loading...</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><div class="skeleton-box"></div></td>
                <td><div class="skeleton-box"></div></td>
                <td><div class="skeleton-box"></div></td>
                <td><div class="skeleton-box"></div></td>
            </tr>
            <tr>
                <td><div class="skeleton-box"></div></td>
                <td><div class="skeleton-box"></div></td>
                <td><div class="skeleton-box"></div></td>
                <td><div class="skeleton-box"></div></td>
            </tr>
            <tr>
                <td><div class="skeleton-box"></div></td>
                <td><div class="skeleton-box"></div></td>
                <td><div class="skeleton-box"></div></td>
                <td><div class="skeleton-box"></div></td>
            </tr>
        </tbody>
    </table>
`;

const { state, actions, callbacks } = store( 'ttft/data-tables', {
	state: {
		isLoading: false,
        searchLabel: ''
	},
    actions: {
        async renderTable() {
            const context = getContext();

            try {
                const { tableType, thinkTank, donor, donationYear, donorType, searchLabel, ajaxUrl, action, nonce } = state;

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
                context.isLoaded = false;

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

                    await actions.destroyTableAsync();

                    const container = document.getElementById( state.elementId );

                    if ( container ) {
                        container.innerHTML = jsonResponse.data;

                        if ( state.table ) {
                            state.table = initTable( `#${state.tableId}` );
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
			state.table = initTable( `#${state.tableId}`, state );
		},
		destroyTable: () => {
			state.table.clear().draw();
			state.table.destroy();
		},
        destroyTableAsync: () => {
            return new Promise( ( resolve ) => {
                actions.destroyTable();
                resolve();
            });
        },
        updateContext: () => {
            const context = getContext();
            context.tableType = state.tableType;
            context.donationYear = state.donationYear;
            context.donorType = state.donorType;
        }
    },
    callbacks: {
        loadAnimation: () => {
            useEffect( () => { 
                const container = document.getElementById( state.elementId ); 
                if ( state.isLoading ) { 
                    container.innerHTML = skeletonTable; 
                } }, [ state.isLoading ] );
        },
        initLog: () => {
            console.log( `Initial State: `, JSON.stringify( state, undefined, 2 )  );
            const { tableType, donationYear, donorType, isLoaded } = getContext();
            console.log( `Initial Context: ${tableType}, isLoaded: ${isLoaded}, ${donationYear}, ${donorType}` );
        },
        logTable: () => {
            const { tableType, thinkTank, donor, donationYear, donorType, isLoading } = state;
            console.log( `State: `, tableType, thinkTank, donor, donationYear, donorType, isLoading );
        },
        logFormData: ( data ) => {
            for ( const [ key, value ] of data.entries() ) {
                console.log( `${ key }: ${ value }` );
            }
        },
        logState: ( key ) => {
            console.log( `key: `, state.key );
        },
        logResponseData: ( data ) => {
            // console.log( 'Raw response:', data );
        },
		logLoading: () => {
			console.log( `IS LOADING: `, state.isLoading );
		},
        log: ( message, data ) => {
            console.log( message, data );
        },
        logError: ( message, error ) => {
            console.error( message, error );
        }
    },
});
