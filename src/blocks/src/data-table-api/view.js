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

import DataTable from 'datatables.net';

let table = null;

const { state, actions, callbacks } = store( 'ttft/data-tables', {
	state: {
		isLoading: false,
		donationYear: 'all',
		donorType: 'all',
		entityType: 'think_tank',
	},
	actions: {
		async fetchData() {
			const url = state.restUrl;

			const params = new URLSearchParams( {
				table_type: state.tableType,
				donor: state.donor,
				think_tank: state.thinkTank,
				donation_year: state.donationYear,
				donor_type: state.donorType,
				search: state.search,
			} );
			
			
            try {
                // Fetch data from the REST API with the new filters.
                const response = await fetch(`${url}?${params}`);
                const result = await response.json();

                if ( result ) {
                    state.data = result.data; // Update the data in the state.
                    state.columns = result.columns; // Update columns if needed.
                } else {
                    console.error('Failed to load data:', result);
                }
            } catch (error) {
                console.error('Error fetching data:', error);
            }
		},
		init: () => {
			table = new DataTable( state.tableId, {
                data: state.data,
                columns: state.columns,
				pageLength: state.pageLength || 50,
				api: true,
				deferLoading: state.pageLength || 50,
				processing: true,
            } );
		},
		update() {
            if ( table ) {
                table.clear(); // Clear existing data.
                table.rows.add( state.data ); // Add new data.
                table.draw(); // Redraw the table.
            }
        }
	},
	callbacks: {
		log: () => {},
		hasStateChanged: () => {
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
		},
	},
} );
