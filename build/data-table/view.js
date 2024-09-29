import * as __WEBPACK_EXTERNAL_MODULE__wordpress_interactivity_8e89b257__ from "@wordpress/interactivity";
/******/ var __webpack_modules__ = ({

/***/ "@wordpress/interactivity":
/*!*******************************************!*\
  !*** external "@wordpress/interactivity" ***!
  \*******************************************/
/***/ ((module) => {

module.exports = __WEBPACK_EXTERNAL_MODULE__wordpress_interactivity_8e89b257__;

/***/ })

/******/ });
/************************************************************************/
/******/ // The module cache
/******/ var __webpack_module_cache__ = {};
/******/ 
/******/ // The require function
/******/ function __webpack_require__(moduleId) {
/******/ 	// Check if module is in cache
/******/ 	var cachedModule = __webpack_module_cache__[moduleId];
/******/ 	if (cachedModule !== undefined) {
/******/ 		return cachedModule.exports;
/******/ 	}
/******/ 	// Create a new module (and put it into the cache)
/******/ 	var module = __webpack_module_cache__[moduleId] = {
/******/ 		// no module.id needed
/******/ 		// no module.loaded needed
/******/ 		exports: {}
/******/ 	};
/******/ 
/******/ 	// Execute the module function
/******/ 	__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 
/******/ 	// Return the exports of the module
/******/ 	return module.exports;
/******/ }
/******/ 
/************************************************************************/
/******/ /* webpack/runtime/make namespace object */
/******/ (() => {
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = (exports) => {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/ })();
/******/ 
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {
/*!********************************!*\
  !*** ./src/data-table/view.js ***!
  \********************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/interactivity */ "@wordpress/interactivity");
/**
 * WordPress dependencies
 */

let initTable = el => {
  const title = document.querySelector('.site-main h1')?.innerText || document.title;
  return new DataTable(el, {
    info: false,
    pageLength: 50,
    layout: {
      bottomEnd: {
        paging: {
          type: 'simple_numbers'
        }
      },
      topEnd: {
        search: {
          placeholder: 'Enter keyword...',
          text: state.searchLabel
        }
      },
      topStart: {
        buttons: [{
          extend: 'csvHtml5',
          title: title,
          text: 'Download Data'
        }]
      }
    }
  });
};
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

// let initTable = ( el, state ) => {
//     const title = document.querySelector( '.site-main h1' )?.innerText || document.title;
//     return new DataTable( el, {
//         info: false,
//         pageLength: 50,
//         layout: {
//             bottomEnd: {
//                 paging: {
//                     type: 'simple_numbers',
//                 },
//             },
//             topEnd: {
//                 search: {
//                     placeholder: 'Enter keyword...',
//                     text: state.searchLabel, // Ensure state is passed as a parameter
//                 },
//             },
//             topStart: {
//                 buttons: [
//                     {
//                         extend: 'csvHtml5',
//                         title: title,
//                         text: 'Download Data',
//                     },
//                 ],
//             },
//         }
//     } );
// };

const {
  state,
  actions,
  callbacks
} = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.store)('ttft/data-tables', {
  state: {
    isLoading: false,
    searchLabel: ''
  },
  actions: {
    async renderTable() {
      const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      try {
        const {
          tableType,
          thinkTank,
          donor,
          donationYear,
          donorType,
          searchLabel,
          ajaxUrl,
          action,
          nonce
        } = state;
        const formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', nonce);
        formData.append('table_type', tableType);
        formData.append('search_label', searchLabel || 'Enter keyword...');
        formData.append('donation_year', donationYear || 'all');
        formData.append('donor_type', donorType || 'all');
        formData.append('think_tank', thinkTank);
        formData.append('donor', donor);
        callbacks.logFormData(formData);
        state.isLoading = true;
        context.isLoaded = false;
        const response = await fetch(ajaxUrl, {
          method: 'POST',
          body: formData
        });
        const responseText = await response.text();
        callbacks.logResponseData(responseText);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        const jsonResponse = JSON.parse(responseText);
        if (jsonResponse.success) {
          state.tableData = jsonResponse.data;
          actions.destroyTable();
          const container = document.getElementById(state.elementId);
          container.innerHTML = '';
          container.innerHTML = jsonResponse.data;
          state.table = initTable(`#${state.tableId}`);
        } else {
          console.error(`Error fetching table data:`, jsonResponse.data);
        }
      } catch (event) {
        console.error(`catch( event ) renderTable:`, event);
      } finally {
        state.isLoading = false;
        context.isLoaded = true;
      }
    },
    initTable: () => {
      state.table = initTable(`#${state.tableId}`, state);
    },
    destroyTable: () => {
      state.table.clear().draw();
      state.table.destroy();
    },
    updateContext: () => {
      const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      context.tableType = state.tableType;
      context.donationYear = state.donationYear;
      context.donorType = state.donorType;
    }
  },
  callbacks: {
    loadAnimation: () => {
      (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
        const container = document.getElementById(state.elementId);
        if (state.isLoading) {
          container.innerHTML = skeletonTable;
        }
      }, [state.isLoading]);
    },
    initLog: () => {
      console.log(`Initial State: `, JSON.stringify(state, undefined, 2));
      const {
        tableType,
        donationYear,
        donorType,
        isLoaded
      } = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      console.log(`Initial Context: ${tableType}, isLoaded: ${isLoaded}, ${donationYear}, ${donorType}`);
    },
    logTable: () => {
      const {
        tableType,
        thinkTank,
        donor,
        donationYear,
        donorType,
        isLoading
      } = state;
      console.log(`State: `, tableType, thinkTank, donor, donationYear, donorType, isLoading);
    },
    logFormData: data => {
      for (const [key, value] of data.entries()) {
        console.log(`${key}: ${value}`);
      }
    },
    logState: key => {
      console.log(`key: `, state.key);
    },
    logResponseData: data => {
      // console.log( 'Raw response:', data );
    },
    logLoading: () => {
      console.log(`IS LOADING: `, state.isLoading);
    },
    log: (message, data) => {
      console.log(message, data);
    },
    logError: (message, error) => {
      console.error(message, error);
    }
  }
});
})();


//# sourceMappingURL=view.js.map