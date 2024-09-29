/**
 * WordPress dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

const { state, actions, callbacks } = store( 'ttft/data-tables', {
	actions: {
		updateYear: () => {
			const { ref } = getElement();
			state.donationYear = ref.value ?? 'all';
		},
		stringifyState: () => {
			state.jsonState = JSON.stringify( state.tableData, null, 2 );
		}
	},
	callbacks: {
		logYear: () => {
			const { donationYear } = state;
			console.log( `donationYear: `, donationYear );
			// const context = getContext();
            // console.log( 'Context:', JSON.stringify( context, undefined, 2 ) );
			// actions.stringifyState();
		},
		isSelected: () => {
			const { ref } = getElement();
			return state.donationYear == ref.value;
		}
	},
} );

