/**
 * WordPress dependencies
 */
import { store, getContext, getElement, useState, useEffect } from '@wordpress/interactivity';

let initTable = ( table ) => {
	const title = document.querySelector( '.site-main h1' )?.innerText || document.title;
    const pageLength = state.pageLength;
	return new DataTable( table, {
		pageLength: 50,
		layout: {
            bottomStart: {
                info: {
                    callback: function ( s, start, end, max, total, result ) {
                        return ``;
                    }
                },
            },
			bottomEnd: {
				paging: {
					type: 'simple_numbers',
				},
                info: {
                    callback: function ( s, start, end, max, total, result ) {
                        return `${max} Records Found.`;
                    }
                }
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

        footerCallback: function( row, data, start, end, display ) {
            const api = this.api();

            if ( 'undefined' == typeof window.jQuery ) {
                return;
            }

            Array.from( api.columns().header() ).forEach( ( header, index ) => {
                if ( header.getAttribute( 'data-summed' ) ) {

                    const total = api.column( index ).data().reduce( function( a, b ) {;
                        return Number( a ) + Number( b );
                    }, 0 );

                    const $column = api.column( index );
                    const $footer = jQuery( $column.footer() );

                    if ( $footer ) {
                        $footer.html( total );
                    }

                }
            });
        }
	} );
}

const { state, actions, callbacks } = store( 'ttft/data-tables', {
	state: {
		isLoading: false,
        pageLength: 50,
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
                formData.append( 'search_label', searchLabel || 'Search' );
                formData.append( 'donation_year', donationYear || 'all' );
                formData.append( 'donor_type', donorType || 'all' );
                formData.append( 'think_tank', thinkTank );
                formData.append( 'donor', donor );

                // console.log( `formData: `, formData );

				state.isLoading = true;
                context.isLoaded = false;

                const response = await fetch( ajaxUrl, {
                    method: 'POST',
                    body: formData,
                });

                const responseText = await response.text();

                // console.log( `responseText: `, responseText );

				if ( ! response.ok ) {
                    throw new Error( `HTTP error! status: ${response.status}` );
                }

                const jsonResponse = JSON.parse( responseText );

                if ( jsonResponse.success ) {
                    state.tableData = jsonResponse.data;

                    // debugger;

                    await actions.destroyTableAsync();

                    const container = document.getElementById( state.elementId );

                    if ( container ) {
                        container.innerHTML = jsonResponse.data;

                        if ( state.table ) {
                            state.table = initTable( `#${state.tableId}` );
                        }
                    }

                } else {
                    // console.error( `Error fetching table data:`, jsonResponse.data );
                }
            } catch ( event ) {
                // console.error( `catch( event ) renderTable:`, event );
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
        },
        generateSkeletonTable: ( columns ) => {
            let skeletonRows = '';
            for ( let i = 0; i < 10; i++ ) {
                skeletonRows += '<tr class="row" width="100%">';
                for ( let j = 0; j < columns; j++ ) {
                    skeletonRows += '<td class="cell"><div class="loader"></div></td>';
                }
                skeletonRows += '</tr>';
            }
            return skeletonRows;
        }
    },
    callbacks: {
        loadAnimation: () => {
            useEffect( () => { 
                const container = document.getElementById( state.elementId );
                if ( state.isLoading && container ) {
                    const columns = container.querySelectorAll( 'thead th' ).length;
                    const skeletonTable = actions.generateSkeletonTable( columns );
                    container.querySelector( 'tbody' ).innerHTML = skeletonTable;
                } }, [ state.isLoading ] );
        },
        initLog: () => {
            // console.log( `Initial State: `, JSON.stringify( state, undefined, 2 )  );
            // const { tableType, donationYear, donorType, isLoaded } = getContext();
            // console.log( `Initial Context: ${tableType}, isLoaded: ${isLoaded}, ${donationYear}, ${donorType}` );
        },
        logTable: () => {
            // const { tableType, thinkTank, donor, donationYear, donorType, isLoading } = state;
            // console.log( `State: `, tableType, thinkTank, donor, donationYear, donorType, isLoading );
        },
        logState: ( key ) => {
            console.log( `key: `, state.key );
        },
		logLoading: () => {
			// console.log( `IS LOADING: `, state.isLoading );
		}
    },
});
