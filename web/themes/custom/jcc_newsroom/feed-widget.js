// var embedArg = "";
// var originUrl = "";
//
// var embeds = document.getElementsByClassName("jcc-newsroom-widget");
// for (let embed of embeds) {
//   embedArg = embed.dataset.subject;
//   originUrl = embed.dataset.origin;
//   embed.insertAdjacentHTML('afterend', '<div class="embed-list" id="jcc_embed_' + embedArg + '"></div>');
//
//   fetch(originUrl + '/feed/news/' + embedArg)
//     .then(function (response) {
//       return response.json();
//     })
//     .then(function (data) {
//       appendData(data.items, embedArg, originUrl);
//     })
//     .catch(function (err) {
//       console.log('error: ' + err);
//     });
//
// }
//
// function appendData(data, embedArg, originUrl) {
//   var mainContainer = document.getElementById('jcc_embed_' + embedArg);
//   for (var i = 0; i < data.length; i++) {
//     var div = document.createElement("div");
//     div.innerHTML = '<h3>' + data[i].title + '</h3><p>' + data[i].content_html + '</p><p><a target="_blank" href="' + originUrl + '/node/' + data[i].id + '">Read Full Article</a></p>';
//     mainContainer.appendChild(div);
//   }
// }

// -------------------------------------------------------------------------

var embedArg = "";
var originUrl = "";

function jsonCallback(data){
  console.log(data);
}

var embeds = document.getElementsByClassName("jcc-newsroom-widget");
for (let embed of embeds) {
  embedArg = embed.dataset.subject;
  originUrl = embed.dataset.origin;
  // originUrl = "https://develop-jcc-newsroom.pantheonsite.io";
  embed.insertAdjacentHTML('afterend', '<div class="embed-list" id="jcc_embed_' + embedArg + '"></div>');

  //makeTheCall(originUrl + '/feed/news/' + embedArg);

  var s = document.createElement("script"),
    callback = "jsonpCallback_" + new Date().getTime(),
    // url = "http://run.plnkr.co/plunks/v8xyYN64V4nqCshgjKms/data-2.json?callback=" + callback;
    url = originUrl + "/themes/custom/jcc_newsroom/test.json?callback=" + callback;
    window[callback] = function (data) {
    // it worked!
    console.log(data);
  };
  s.src = url;
  document.body.appendChild(s);

}




