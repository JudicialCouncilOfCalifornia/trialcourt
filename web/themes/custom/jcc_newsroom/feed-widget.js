var embedArg = [];
var originUrl = [];
var displayCount = [];
var hideDescription = [];
var hideDate = [];
var hideThumbnail = [];

function jsonCallback(jsonData, index){
  let data = jsonData.items;
  displayCount[index] = data.length < displayCount[index] ? data.length : displayCount[index];
  var mainContainer = document.getElementById('jcc_embed_' + embedArg[index] + '_' + index);

  for (var i = 0; i < displayCount[index]; i++) {
    // Publish date & title
    var divHeader = document.createElement("div");
    divHeader.className = "usa-card__header";
    divHeader.innerHTML = hideDate[index] != "yes" ? '<div class="jcc-element-date usa-card__brow">' + data[i].date_published + '</div>' : '';
    divHeader.innerHTML += '<h3 class="jcc-element-title usa-card__heading"><a href="' + data[i].url + '">' + data[i].title + '</a></h3>';

    // Teaser
    var divBody = document.createElement("div");
    divBody.className = "usa-card__body usa-prose";
    divBody.innerHTML = '<p class="jcc-element-description usa-card__body usa-prose">' + data[i].content_html + '</p>';

    // Card text ... collect date, title, & body as single block
    var divText = document.createElement("div");
    divText.className = "usa-card__content";
    divText.appendChild(divHeader);
    if (hideDescription[index] != "yes") {
      divText.appendChild(divBody);
    }

    // Card image ... extract Drupal image element in JSON result (e.g. media library thumbnail)
    var image = decodeURIComponent(data[i].image);
    var imageDomain = window.location.hostname;
    var imagePath = image.match(/src="([^"]*)/)[1];
    var imageAlt = image.match(/alt="([^"]*)/)[1];
    var divImage = document.createElement("div");
    divImage.className = "jcc-element-image usa-card__media";
    divImage.innerHTML = '<a href="' + data[i].url + '"><span><img src="' + imageDomain + imagePath + '" alt="' + imageAlt + '"></span></a>';

    // Card container ... collect text block & image into single container
    var divContainer = document.createElement("div");
    divContainer.className = "usa-card__container";
    divContainer.appendChild(divText);
    if (hideThumbnail[index] != "yes") {
      divContainer.appendChild(divImage);
    }

    // Card ... completed component
    var div = document.createElement("div");
    div.className = hideThumbnail[index] != 'yes' ? "jcc-news-element usa-card usa-card--borderless usa-card--media-left" : "jcc-news-element usa-card usa-card--borderless";
    div.appendChild(divContainer);

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
  hideThumbnail[i] = embed.dataset.hideThumbnail || "no";
  embed.insertAdjacentHTML('beforebegin', '<div class="embed-list" id="jcc_embed_' + embedArg[i] + '_' + i + '"></div>');

  let s = document.createElement("script"),
    callback = "jsonpCallback_" + new Date().getTime(),
    url = originUrl[i] + '/feed/news/' + embedArg[i] + "?callback=" + callback + "&index=" + i;
    s.type = "application/javascript";
    s.src = url;
  document.body.appendChild(s);
});
