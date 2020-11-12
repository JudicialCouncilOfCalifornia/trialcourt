var embedArg = "";
var originUrl = "";

function jsonCallback(jsonData){
  let data = jsonData.items;
  var mainContainer = document.getElementById('jcc_embed_' + embedArg);
  for (var i = 0; i < data.length; i++) {
    var div = document.createElement("div");
    div.innerHTML = '<h3>' + data[i].title + '</h3><p>' + data[i].content_html + '</p><p><a target="_blank" href="' + originUrl + '/node/' + data[i].id + '">Read Full Article</a></p>';
    mainContainer.appendChild(div);
  }
}

var embeds = document.getElementsByClassName("jcc-newsroom-widget");
for (let embed of embeds) {
  embedArg = embed.dataset.subject;
  originUrl = embed.dataset.origin;
  embed.insertAdjacentHTML('afterend', '<div class="embed-list" id="jcc_embed_' + embedArg + '"></div>');

  var s = document.createElement("script"),
    callback = "jsonpCallback_" + new Date().getTime(),
    url = originUrl + '/feed/news/' + embedArg + "?callback=" + callback;
    s.type = "application/javascript";
    s.src = url;
  document.body.appendChild(s);
}




