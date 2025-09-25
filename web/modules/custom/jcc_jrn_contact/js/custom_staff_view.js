(function (Drupal) {
  
  Drupal.behaviors.columnHighlightOnSort = {
     attach: function (context) {
      once('column-highlight', '.staff-table table', context).forEach(table => {
        const headers = table.querySelectorAll('thead th');
        const bodyRows = table.querySelectorAll('tbody tr');
        function highlightActiveColumn() {               
          headers.forEach(h => h.classList.remove('is-active-highlight'));
          bodyRows.forEach(row => {
            Array.from(row.children).forEach(cell => cell.classList.remove('col-highlight-cell'));
          });

          const activeTh = table.querySelector('thead th.is-active');
          if (!activeTh) return;

          const colIndex = Array.from(activeTh.parentNode.children).indexOf(activeTh);

          activeTh.classList.add('is-active-highlight');
          bodyRows.forEach(row => {
            if (row.children[colIndex]) {
              row.children[colIndex].classList.add('col-highlight-cell');
            }
          });
        }
        highlightActiveColumn();
        headers.forEach(th => {
          th.addEventListener('click', () => {
            setTimeout(highlightActiveColumn, 0); 
          });
        });
      });
    }
  };  
})(Drupal);
