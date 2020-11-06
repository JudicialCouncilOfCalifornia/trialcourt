// <a class="jcc-newsroom-widget" href="http://newsroom.lndo.site/feed/news/32" data-subject="32" data-origin="http://newsroom.lndo.site">Covid-19 News</a>
// <script async src="http://newsroom.lndo.site/themes/custom/jcc_newsroom/feed-widget.js" charset="utf-8"></script>

var embeds = document.getElementsByClassName("jcc-newsroom-widget");
for (let embed of embeds) {
  let embedArg = embed.dataset.subject;
  let originUrl = embed.dataset.origin;
  embed.insertAdjacentHTML('afterend', '<div class="embed-list" id="jcc_embed_' + embedArg + '"></div>');


  fetch(originUrl + '/feed/news/' . originUrl)
    .then(function (response) {
      return response.json();
    })
    .then(function (data) {
      appendData(data.items, embedArg, originUrl);
    })
    .catch(function (err) {
      console.log('error: ' + err);
    });
}

function appendData(data, embedArg, originUrl) {
  var mainContainer = document.getElementById('jcc_embed_' + embedArg);
  for (var i = 0; i < data.length; i++) {
    var div = document.createElement("div");
    div.innerHTML = '<h3>' + data[i].title + '</h3><p>' + data[i].content_html + '</p><p><a target="_blank" href="' + originUrl + '/node/' + data[i].id + '">Read Full Article</a></p>';
    mainContainer.appendChild(div);
  }
}
