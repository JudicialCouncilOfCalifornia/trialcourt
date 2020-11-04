console.log('test');

var jsonRequestUrl = 'http://www.reddit.com/r/funny/comments/1v6rrq.json';
var decoder = $('<div />');
var decodedText = '';

$.getJSON(jsonRequestUrl, function foo(result) {
  var elements = result[1].data.children.slice(0, 10);

  $.each(elements, function (index, value) {
    decoder.html(value.data.body_html);
    decodedText += decoder.text();
  });

  $('#content').append(decodedText);
});
