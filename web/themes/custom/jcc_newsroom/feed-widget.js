var embedArg = "";
var originUrl = "";

function jsonCallback(jsonData){
  let data = jsonData.items;
  var mainContainer = document.getElementById('jcc_embed_' + embedArg);
  for (var i = 0; i < data.length; i++) {
    var div = document.createElement("div");
    div.className = "jcc-news-element";
    div.innerHTML = '<div class="jcc-element-date">' + data[i].date_published + '</div><div class="jcc-element-title"><a href="' + originUrl + '/node/' + data[i].id + '">' + data[i].title + '</a></div><p class="jcc-element-description">' + data[i].content_html + '</p><p class="jcc-element-read-more"><a target="_blank" href="' + originUrl + '/node/' + data[i].id + '">Read Full Article</a></p>';
    mainContainer.appendChild(div);
  }
}

var embeds = document.getElementsByClassName("jcc-newsroom-widget");
for (let embed of embeds) {
  embedArg = embed.dataset.subject;
  originUrl = embed.dataset.origin;
  embed.insertAdjacentHTML('beforebegin', '<div class="embed-list" id="jcc_embed_' + embedArg + '"></div>');

  var s = document.createElement("script"),
    callback = "jsonpCallback_" + new Date().getTime(),
    url = originUrl + '/feed/news/' + embedArg + "?callback=" + callback;
    s.type = "application/javascript";
    s.src = url;
  document.body.appendChild(s);
}




