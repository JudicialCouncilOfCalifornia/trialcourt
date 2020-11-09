var embedArg = "";
var originUrl = "";

var embeds = document.getElementsByClassName("jcc-newsroom-widget");
for (let embed of embeds) {
  embedArg = embed.dataset.subject;
  originUrl = embed.dataset.origin;
  embed.insertAdjacentHTML('afterend', '<div class="embed-list" id="jcc_embed_' + embedArg + '"></div>');

  // fetch(originUrl + '/feed/news/' + embedArg + "?callback=appendData")
  //   .then(function (response) {
  //     return response.json();
  //   })
  //   .then(function (data) {
  //     appendData(data.items, embedArg, originUrl);
  //   })
  //   .catch(function (err) {
  //     console.log('error: ' + err);
  //   });

  var script = document.createElement('script');
  script.src = originUrl + '/feed/news/' + embedArg + '?callback=appendData';

  document.getElementsByTagName('head')[0].appendChild(script);

}

function appendData(data, embedArg, originUrl) {
  var mainContainer = document.getElementById('jcc_embed_' + embedArg);
  for (var i = 0; i < data.length; i++) {
    var div = document.createElement("div");
    div.innerHTML = '<h3>' + data[i].title + '</h3><p>' + data[i].content_html + '</p><p><a target="_blank" href="' + originUrl + '/node/' + data[i].id + '">Read Full Article</a></p>';
    mainContainer.appendChild(div);
  }
}

