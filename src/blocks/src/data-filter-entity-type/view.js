/**
 * WordPress dependencies
 */
import { 
	store, 
	getContext, 
	getElement, 	
	useState,
	useEffect 
} from '@wordpress/interactivity';

const { state, actions, callbacks } = store( 'ttft/data-tables', {
	actions: {
		toggleThinkTank: ( event ) => {
			event.preventDefault();
			const context = getContext();
			const { thinkTankSelected } = state;
			state.thinkTankSelected = !thinkTankSelected;
			state.donorSelected = !state.thinkTankSelected;
			if( state.thinkTankSelected ) {
				state.entityType = 'think_tank';
			}
			// console.log( `thinkTankSelected: `, state.thinkTankSelected, `donorSelected: `, state.donorSelected, `state.entityType: `, state.entityType );
		},
		toggleDonor: ( event ) => {
			event.preventDefault();
			const context = getContext();
			const { donorSelected } = state;
			state.donorSelected = !donorSelected;
			state.thinkTankSelected = !state.donorSelected;
			if( state.donorSelected ) {
				state.entityType = 'donor';
			}
			// console.log( `donorSelected: `, state.donorSelected, `thinkTankSelected: `, state.thinkTankSelected, `state.entityType: `, state.entityType );
		},
	},
	callbacks: {
		logType: () => {
			const { entityType } = state;
			// console.log( `entityType: `, entityType );
		},
	},
} );
