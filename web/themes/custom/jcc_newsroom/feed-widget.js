var embedArg = [];
var originUrl = [];
var displayCount = [];
var hideDescription = [];
var hideDate = [];

function jsonCallback(jsonData, index){
  let data = jsonData.items;
  displayCount[index] = data.length < displayCount[index] ? data.length : displayCount[index];
  var mainContainer = document.getElementById('jcc_embed_' + embedArg[index] + '_' + index);

  for (var i = 0; i < displayCount[index]; i++) {
    var div = document.createElement("div");
    div.className = "jcc-news-element";
    div.innerHTML = hideDate[index] == "no" ? '<div class="jcc-element-date">' + data[i].date_published + '</div>' : '';
    div.innerHTML += '<div class="jcc-element-title"><a href="' + data[i].url + '">' + data[i].title + '</a></div>';
    div.innerHTML += hideDescription[index] == "no" ? '<p class="jcc-element-description">' + data[i].content_html + '</p>' : '';

    mainContainer.appendChild(div);
  }
}

var embeds = document.getElementsByClassName("jcc-newsroom-widget");
Array.prototype.forEach.call(embeds, function (embed, i) {
  embedArg[i] = embed.dataset.subject;
  originUrl[i] = embed.dataset.origin;
  displayCount[i] = embed.dataset.count || "10";
  hideDescription[i] = embed.dataset.hideDescription || "no";
  hideDate[i] = embed.dataset.hideDate || "no";
  embed.insertAdjacentHTML('beforebegin', '<div class="embed-list" id="jcc_embed_' + embedArg[i] + '_' + i + '"></div>');

  let s = document.createElement("script"),
    callback = "jsonpCallback_" + new Date().getTime(),
    url = originUrl[i] + '/feed/news/' + embedArg[i] + "?callback=" + callback + "&index=" + i;
    s.type = "application/javascript";
    s.src = url;
  document.body.appendChild(s);
});
