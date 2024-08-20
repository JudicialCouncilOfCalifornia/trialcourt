/**
 * Opinion remote files link builder.
 **/

(function ($, Drupal) {
  Drupal.behaviors.opinionlinksbuilder = {
    attach: function attach(context, drupalSettings) {
      const type = document.querySelector('.view-results').classList[ document.querySelector('.view-results').classList.length-1 ];
      const proxyPass = 'https://intense-anchorage-92873-2170464d26cd.herokuapp.com/';
      const base = 'https://www.courts.ca.gov/opinions/';
      const pubPath = 'documents/';
      const pubExtPath = 'archive/';
      const pubRevPath = 'revpub/';
      const nonpubPath = 'nonpub/';
      const nonpubRevPath = 'revnppub/';
      const readmorebtns = Array.from(document.querySelectorAll('.read-more__action'));

      function remoteFileCheck(proxyPass, url, caseNumber, extension) {
        let http = new XMLHttpRequest();
        let request = proxyPass + url + caseNumber + '.' +extension;
        http.open('HEAD', request, false);
        http.send();
        return http.status != 404;
      }
      function buttonsBuilder(url, caseNumber, extension) {
        let buttons = [
          '<a class="button button--primary button--normal icon--arrow" target="_blank" aria_label="View or download opinion PDF file for case ' + caseNumber + '" href="' + url + caseNumber + '.PDF">PDF</a>',
          '<a class="button button--secondary button--normal icon--arrow" target="_blank" aria_label="View or download opinion Word file for case ' + caseNumber + '" href="' + url + caseNumber + '.' + extension + '">' + extension + '</a>',
        ];
       return buttons[0] + ' ' + buttons[1];
      }

      $(document).ready(function () {
        readmorebtns.forEach(readmorebtn => {
          if (!readmorebtn.classList.contains('js-readmore')) {
            readmorebtn.addEventListener('click', (e) => {
              let container = readmorebtn.parentElement.parentElement.parentElement.parentElement.parentElement.parentElement;
              let metadata = container.querySelector('.result-excerpt__brow-notation').innerText;
              let caseNumber = container.querySelector('.result-excerpt__brow-primary').innerText;
              let isReviewGranted = metadata.match( /Review Granted/g );
              let readMoreContainer = readmorebtn.parentElement.parentElement;
              let readMoreContent = readMoreContainer.querySelector('.read-more__content');
              let throbber = readMoreContent.getElementsByClassName('throbber');

              if (caseNumber && throbber) {
                let path;
                switch (type) {
                  case 'published_extended':
                    path = pubExtPath;
                    break;

                  case 'unpublished':
                    path = nonpubPath;
                    if (isReviewGranted) {
                      path = nonpubRevPath;
                    }
                    break;

                  default:
                    path = pubPath;
                    if (isReviewGranted) {
                      path = pubRevPath;
                    }
                }
                let extension = 'DOCX';
                let url = base + path;

                if (remoteFileCheck(proxyPass, url, caseNumber, extension)) {
                  readMoreContent.innerHTML =  buttonsBuilder(url, caseNumber, extension);
                }
                else {
                  // If DOCX not found, try DOC.
                  let extension = 'DOC';
                  if (remoteFileCheck(proxyPass, url, caseNumber, extension)) {
                    readMoreContent.innerHTML = buttonsBuilder(url, caseNumber, extension);
                  }
                  else {
                    // If extended posts not found under /archive try /documents.
                    if (type === 'published_extended') {
                      let extension = 'DOCX';
                      let url = base + pubPath;

                      if (remoteFileCheck(proxyPass, url, caseNumber, extension)) {
                        readMoreContent.innerHTML =  buttonsBuilder(url, caseNumber, extension);
                      }
                      else {
                        let extension = 'DOC';
                        if (remoteFileCheck(proxyPass, url, caseNumber, extension)) {
                          readMoreContent.innerHTML = buttonsBuilder(url, caseNumber, extension);
                        }
                      }
                    }
                    else {
                      readMoreContent.innerHTML = 'Links to opinion documents cannot be provided at this time.';
                    }
                  }
                }
              }
            });
          }
        });
      });
    }
  }
})(jQuery, Drupal);
