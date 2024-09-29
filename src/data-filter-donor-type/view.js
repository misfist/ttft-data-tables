/**
 * WordPress dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

const { state, actions, callbacks } = store( 'ttft/data-tables', {
	actions: {
		updateType: () => {
			const context = getContext();
			const { ref } = getElement();
			state.donorType = ref.value;
		}
	},
	callbacks: {
		logType: () => {
			const { donorType } = state;
			console.log( `donorType: `, donorType );
			// const context = getContext();
            // console.log( 'Context:', JSON.stringify( context, undefined, 2 ) );
			// actions.stringifyState();
		},
		isSelected: () => {
			const { ref } = getElement();
			return state.donorType == ref.value;
		}
	},
} );
