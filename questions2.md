# Group 2

## Which HTML will visually show text but exclude it from the accessibility tree without using CSS?
<p hidden>X</p>
<p aria-hidden="true">X</p>
<p style="display:none">X</p>
<p>X</p>
~~<p aria-hidden="true">X</p>~~

## Which HTML causes the browser to implicitly insert a <tbody> element?
<table><tr><td>X</td></tr></table>
<table><thead></thead></table>
<table><caption>X</caption></table>
<table></table>
~~<table><tr><td>X</td></tr></table>~~

## Which HTML makes a form control focusable but impossible to submit?
<input readonly name="x">
<input disabled name="x">
<input hidden name="x">
<input name="x">
~~<input disabled name="x">~~

## Which HTML produces valid markup but breaks document outline logic?
<h1>A</h1><h2>B</h2>
<section><h1>A</h1></section>
<h1>A</h1><h4>B</h4>
<article><h1>A</h1></article>
~~<h1>A</h1><h4>B</h4>~~

## Which HTML will submit multiple values under the same key without arrays?
<input name="x"><input name="x">
<input name="x[]"><input name="x[]">
<input id="x"><input id="x">
<input><input>
~~<input name="x"><input name="x">~~

## Which HTML visually looks like a button but has no default keyboard behavior?
<button>Go</button>
<input type="submit">
<a href="#">Go</a>
<span>Go</span>
~~<span>Go</span>~~

## Which HTML causes a browser to silently close a paragraph tag?
<p>One<p>Two
<div>One<div>Two</div>
<span>One<span>Two</span>
<section>One<section>Two</section>
~~<p>One<p>Two~~

## Which HTML causes an element to exist in DOM but not layout or accessibility tree?
<div hidden>X</div>
<div aria-hidden="true">X</div>
<div style="display:none">X</div>
<div>X</div>
~~<div hidden>X</div>~~

## Which HTML creates an invalid but browser-repaired form structure?
<form><form></form></form>
<form></form><form></form>
<form><input></form>
<form></form>
~~<form><form></form></form>~~

## Which HTML produces a clickable element that submits a form unintentionally?
<button>Save</button>
<button type="button">Save</button>
<input type="button">
<div>Save</div>
~~<button>Save</button>~~

## Which HTML hides text visually but keeps it readable by screen readers?
<span hidden>X</span>
<span style="display:none">X</span>
<span>X</span>
<span aria-hidden="false">X</span>
~~<span aria-hidden="false">X</span>~~

## Which HTML produces quotation marks automatically in rendered output?
<blockquote>X</blockquote>
<q>X</q>
<cite>X</cite>
<em>X</em>
~~<q>X</q>~~

## Which HTML causes form validation to be bypassed entirely?
<input required>
<input disabled required>
<input readonly required>
<input value="">
~~<input disabled required>~~

## Which HTML makes an image decorative only?
<img src="x.jpg">
<img src="x.jpg" alt="">
<img src="x.jpg" title="x">
<img src="x.jpg" aria-hidden="false">
~~<img src="x.jpg" alt="">~~

## Which HTML creates a heading that exists but is ignored in outline calculation?
<h2>X</h2>
<p><h2>X</h2></p>
<section><h2>X</h2></section>
<article><h2>X</h2></article>
~~<p><h2>X</h2></p>~~

## Which HTML produces an anchor that is focusable but inert?
<a href="#">X</a>
<a href="">X</a>
<a>X</a>
<a href="/">X</a>
~~<a>X</a>~~

## Which HTML causes malformed list rendering but no browser error?
<ul><li>A<li>B<li>C</ul>
<ul><li>A</li><li>B</li></ul>
<ol><li>A</li></ol>
<dl><dt>A</dt></dl>
~~<ul><li>A<li>B<li>C</ul>~~

## Which HTML results in duplicate accessibility names?
<input placeholder="Email">
<label>Email<input></label>
<input aria-label="Email">
<label for="e">Email</label><input id="e">
~~<label>Email<input></label>~~

## Which HTML creates an implicit sectioning root?
<article><h1>X</h1></article>
<div><h1>X</h1></div>
<p><strong>X</strong></p>
<span>X</span>
~~<article><h1>X</h1></article>~~

## Which HTML causes the browser to infer missing <html> and <body>?
<p>X</p>
<html><p>X</p></html>
<body><p>X</p></body>
<head></head>
~~<p>X</p>~~

## Which HTML makes a table header lose association semantics?
<th scope="col">X</th>
<th>X</th>
<td><strong>X</strong></td>
<th scope="row">X</th>
~~<td><strong>X</strong></td>~~

## Which HTML produces a progress indicator with unknown maximum?
<progress value="3"></progress>
<progress value="3" max="10"></progress>
<meter value="3"></meter>
<meter value="3" max="10"></meter>
~~<progress value="3"></progress>~~

## Which HTML makes content appear interactive but has no semantic role?
<button>X</button>
<a href="#">X</a>
<input type="submit">
<div>X</div>
~~<div>X</div>~~

## Which HTML breaks label-input association silently?
<label for="x">X</label><input id="x">
<label>X<input></label>
<label>X</label><input>
<input aria-labelledby="x">
~~<label>X</label><input>~~

## Which HTML causes an iframe to be inaccessible?
<iframe src="x.html"></iframe>
<iframe src="x.html" title="X"></iframe>
<iframe title="X"></iframe>
<iframe></iframe>
~~<iframe src="x.html"></iframe>~~

## Which HTML creates conflicting interactive roles?
<button><a>X</a></button>
<a><span>X</span></a>
<div><a>X</a></div>
<button>X</button>
~~<button><a>X</a></button>~~

## Which HTML auto-closes table rows unexpectedly?
<table><tr><td>A<tr><td>B</table>
<table><tr><td>A</td></tr></table>
<table></table>
<table><thead></thead></table>
~~<table><tr><td>A<tr><td>B</table>~~

## Which HTML hides content from tab order but not from view?
<input hidden>
<input disabled>
<input readonly>
<input type="text">
~~<input readonly>~~

## Which HTML results in malformed but displayed quotation structure?
<q><p>X</p></q>
<q>X</q>
<blockquote>X</blockquote>
<cite>X</cite>
~~<q><p>X</p></q>~~

## Which HTML creates navigation landmark misuse?
<nav><p>X</p></nav>
<nav><a>X</a></nav>
<div><a>X</a></div>
<nav></nav>
~~<nav><p>X</p></nav>~~

## Which HTML creates an element excluded from form submission only?
<input readonly name="x">
<input disabled name="x">
<input hidden name="x">
<input name="x">
~~<input disabled name="x">~~

## Which HTML breaks document outline using valid tags?
<h1>A</h1><h3>B</h3>
<h1>A</h1><h2>B</h2>
<section><h1>A</h1></section>
<article><h1>A</h1></article>
~~<h1>A</h1><h3>B</h3>~~

## Which HTML causes nested anchor auto-correction?
<a><a>X</a></a>
<a><span>X</span></a>
<div><a>X</a></div>
<a>X</a>
~~<a><a>X</a></a>~~

## Which HTML hides content visually and structurally?
<span hidden>X</span>
<span aria-hidden="true">X</span>
<span>X</span>
<span aria-hidden="false">X</span>
~~<span hidden>X</span>~~

## Which HTML creates ambiguous form purpose?
<input placeholder="Email">
<label>Email<input></label>
<input aria-label="Email">
<label for="e">Email</label><input id="e">
~~<input placeholder="Email">~~

## Which HTML produces an implicit heading section boundary?
<article><h1>X</h1></article>
<div><h1>X</h1></div>
<p><strong>X</strong></p>
<span>X</span>
~~<article><h1>X</h1></article>~~

## Which HTML allows submission with invisible data?
<input hidden value="x">
<input disabled value="x">
<input readonly value="x">
<input value="">
~~<input hidden value="x">~~

## Which HTML causes list items to exist without a parent list?
<li>X</li>
<ul><li>X</li></ul>
<ol><li>X</li></ol>
<dl><dt>X</dt></dl>
~~<li>X</li>~~

## Which HTML creates an element visible but ignored by screen readers?
<p aria-hidden="true">X</p>
<p hidden>X</p>
<p style="display:none">X</p>
<p>X</p>
~~<p aria-hidden="true">X</p>~~

## Which HTML breaks figure-caption relationship?
<figure><figcaption>X</figcaption></figure>
<figure><img><figcaption>X</figcaption></figure>
<figcaption>X</figcaption>
<figure></figure>
~~<figcaption>X</figcaption>~~

## Which HTML causes a form to submit when Enter is pressed inside a text field?
<form><input type="text"></form>
<form><input type="text"><input type="button"></form>
<form><input type="text" disabled></form>
<form><div></div></form>
~~<form><input type="text"></form>~~

## Which HTML creates duplicate IDs without visible browser error?
<div id="x"></div><div id="x"></div>
<div class="x"></div><div class="x"></div>
<span id="x"></span>
<div></div>
~~<div id="x"></div><div id="x"></div>~~

## Which HTML causes browser to auto-close an anchor tag?
<a>One<a>Two</a>
<a>One</a><a>Two</a>
<div><a>One</a></div>
<span><a>One</a></span>
~~<a>One<a>Two</a>~~

## Which HTML makes text selectable but not focusable?
<span>X</span>
<button>X</button>
<a href="#">X</a>
<input value="X">
~~<span>X</span>~~

## Which HTML causes malformed table rows to be repaired by the browser?
<table><tr><td>A<tr><td>B</table>
<table><tr><td>A</td></tr></table>
<table><thead></thead></table>
<table></table>
~~<table><tr><td>A<tr><td>B</table>~~

## Which HTML creates an element that exists only for assistive technology?
<span hidden>X</span>
<span aria-hidden="true">X</span>
<span aria-hidden="false">X</span>
<span>X</span>
~~<span aria-hidden="false">X</span>~~

## Which HTML produces a heading visually but not semantically?
<h2>X</h2>
<p><strong>X</strong></p>
<section><h2>X</h2></section>
<article><h2>X</h2></article>
~~<p><strong>X</strong></p>~~

## Which HTML causes list items to be inferred implicitly?
<ul>A B C</ul>
<ul><li>A<li>B<li>C</ul>
<ol><li>A</li></ol>
<dl><dt>A</dt></dl>
~~<ul><li>A<li>B<li>C</ul>~~

## Which HTML makes a form control excluded from accessibility tree but still visible?
<input hidden>
<input aria-hidden="true">
<input disabled>
<input readonly>
~~<input aria-hidden="true">~~

## Which HTML causes multiple outline roots in a document?
<section><h1>A</h1></section><section><h1>B</h1></section>
<div><h1>A</h1></div>
<main><h1>A</h1></main>
<article><h1>A</h1></article>
~~<section><h1>A</h1></section><section><h1>B</h1></section>~~

## Which HTML makes a <label> useless without error?
<label for="x">Name</label><input id="y">
<label>Name<input id="x"></label>
<label for="x">Name</label><input id="x">
<label id="n">Name</label><input aria-labelledby="n">
~~<label for="x">Name</label><input id="y">~~

## Which HTML creates an interactive element that cannot be activated by keyboard?
<button>X</button>
<input type="submit">
<a href="#">X</a>
<div>X</div>
~~<div>X</div>~~

## Which HTML causes a browser to infer missing <head> and <body>?
<p>X</p>
<html><p>X</p></html>
<body><p>X</p></body>
<head></head>
~~<p>X</p>~~

## Which HTML hides content from layout but not from accessibility?
<span hidden>X</span>
<span style="display:none">X</span>
<span aria-hidden="true">X</span>
<span aria-hidden="false">X</span>
~~<span aria-hidden="false">X</span>~~

## Which HTML causes a submit button to exist without text?
<button></button>
<input type="submit">
<input type="button">
<div></div>
~~<input type="submit">~~

## Which HTML produces invalid nesting that browsers silently repair?
<p><div>X</div></p>
<div><p>X</p></div>
<p><span>X</span></p>
<p>X</p>
~~<p><div>X</div></p>~~

## Which HTML creates an empty but focusable element?
<button></button>
<div></div>
<span></span>
<p></p>
~~<button></button>~~

## Which HTML causes ambiguous link purpose for screen readers?
<a href="#">Click</a>
<a href="/">Home</a>
<a href="">Link</a>
<a>Link</a>
~~<a href="">Link</a>~~

## Which HTML makes an image announced as "image" only?
<img src="x.jpg">
<img src="x.jpg" alt="">
<img src="x.jpg" alt="image">
<img src="x.jpg" aria-hidden="true">
~~<img src="x.jpg" alt="image">~~

## Which HTML creates a form that cannot submit any data?
<form><input></form>
<form><input disabled></form>
<form><button></button></form>
<form><input readonly></form>
~~<form><input disabled></form>~~

## Which HTML causes browser to auto-insert <tbody>?
<table><tr><td>X</td></tr></table>
<table><caption>X</caption></table>
<table><thead></thead></table>
<table></table>
~~<table><tr><td>X</td></tr></table>~~

## Which HTML creates a heading that breaks logical hierarchy?
<h1>A</h1><h3>B</h3>
<h1>A</h1><h2>B</h2>
<section><h1>A</h1></section>
<article><h1>A</h1></article>
~~<h1>A</h1><h3>B</h3>~~

## Which HTML creates a meter with meaningless value?
<meter value="5"></meter>
<meter value="5" max="10"></meter>
<progress value="5"></progress>
<progress value="5" max="10"></progress>
~~<meter value="5"></meter>~~

## Which HTML creates a progress bar without range?
<progress></progress>
<progress value="2"></progress>
<progress max="10"></progress>
<progress value="2" max="10"></progress>
~~<progress></progress>~~

## Which HTML creates non-semantic grouping?
<section>
<article>
<nav>
<div>
~~<div>~~

## Which HTML hides content but keeps it focusable?
<input hidden>
<input style="display:none">
<input readonly>
<input disabled>
~~<input readonly>~~

## Which HTML creates invalid but rendered list structure?
<ul><div>X</div></ul>
<ul><li>X</li></ul>
<ol><li>X</li></ol>
<dl><dt>X</dt></dl>
~~<ul><div>X</div></ul>~~

## Which HTML causes browser to ignore a heading completely?
<p><h2>X</h2></p>
<section><h2>X</h2></section>
<article><h2>X</h2></article>
<div><h2>X</h2></div>
~~<p><h2>X</h2></p>~~

## Which HTML breaks caption association in tables?
<table><tbody></tbody><caption>X</caption></table>
<table><caption>X</caption><tbody></tbody></table>
<table></table>
<table><thead></thead></table>
~~<table><tbody></tbody><caption>X</caption></table>~~

## Which HTML creates duplicate form submission keys?
<input name="x"><input name="x">
<input id="x"><input id="x">
<input><input>
<input value="x"><input value="y">
~~<input name="x"><input name="x">~~

## Which HTML causes nested form correction?
<form><form></form></form>
<form></form><form></form>
<form><input></form>
<form></form>
~~<form><form></form></form>~~

## Which HTML creates an iframe inaccessible to screen readers?
<iframe src="x.html"></iframe>
<iframe src="x.html" title="X"></iframe>
<iframe title="X"></iframe>
<iframe></iframe>
~~<iframe src="x.html"></iframe>~~

## Which HTML creates misleading emphasis without semantic meaning?
<strong>X</strong>
<em>X</em>
<b>X</b>
<mark>X</mark>
~~<b>X</b>~~

## Which HTML creates an anchor that cannot be activated?
<a>X</a>
<a href="#">X</a>
<a href="/">X</a>
<a href="">X</a>
~~<a>X</a>~~

## Which HTML creates invisible content but keeps DOM presence?
<span hidden>X</span>
<span style="display:none">X</span>
<span>X</span>
<span aria-hidden="false">X</span>
~~<span aria-hidden="false">X</span>~~

## Which HTML creates invalid nesting inside headings?
<h1><p>X</p></h1>
<h1>X</h1>
<section><h1>X</h1></section>
<article><h1>X</h1></article>
~~<h1><p>X</p></h1>~~

## Which HTML creates an element skipped by validation?
<input required>
<input disabled required>
<input readonly required>
<input value="">
~~<input disabled required>~~

## Which HTML makes a list item valid without list parent?
<li>X</li>
<ul><li>X</li></ul>
<ol><li>X</li></ol>
<dl><dt>X</dt></dl>
~~<li>X</li>~~

## Which HTML causes browser to infer missing <tr>?
<table><td>X</td></table>
<table><tr><td>X</td></tr></table>
<table></table>
<table><thead></thead></table>
~~<table><td>X</td></table>~~

## Which HTML hides content only from screen readers?
<span hidden>X</span>
<span style="display:none">X</span>
<span aria-hidden="true">X</span>
<span>X</span>
~~<span aria-hidden="true">X</span>~~

## Which HTML produces clickable text without link semantics?
<a href="#">X</a>
<button>X</button>
<span>X</span>
<input type="submit">
~~<span>X</span>~~

## Which HTML creates malformed but accepted blockquote?
<blockquote><p>X</p></blockquote>
<blockquote>X</blockquote>
<blockquote><div>X</div></blockquote>
<blockquote></blockquote>
~~<blockquote><div>X</div></blockquote>~~

## Which HTML makes a form submit even with no submit button?
<form><input></form>
<form><div></div></form>
<form></form>
<form><input type="button"></form>
~~<form><input></form>~~

## Which HTML creates ambiguous navigation landmark?
<nav><p>X</p></nav>
<nav><a>X</a></nav>
<div><a>X</a></div>
<nav></nav>
~~<nav><p>X</p></nav>~~

## Which HTML creates invalid but rendered table header?
<table><th>X</th></table>
<table><tr><th>X</th></tr></table>
<table><thead></thead></table>
<table></table>
~~<table><th>X</th></table>~~

## Which HTML makes form data invisible but submitted?
<input hidden value="x">
<input disabled value="x">
<input readonly value="x">
<input value="">
~~<input hidden value="x">~~

## Which HTML creates conflicting interactive semantics?
<button><a>X</a></button>
<a><span>X</span></a>
<div><a>X</a></div>
<button>X</button>
~~<button><a>X</a></button>~~

## Which HTML creates a heading skipped in accessibility tree?
<h2 aria-hidden="true">X</h2>
<h2>X</h2>
<section><h2>X</h2></section>
<article><h2>X</h2></article>
~~<h2 aria-hidden="true">X</h2>~~

## Which HTML causes invalid but visible form structure?
<form><p><form></p></form>
<form><input></form>
<form></form>
<form><button></button></form>
~~<form><p><form></p></form>~~

## Which HTML creates misleading form label text?
<input placeholder="Name">
<label>Name<input></label>
<input aria-label="Name">
<label for="n">Name</label><input id="n">
~~<input placeholder="Name">~~

## Which HTML causes browser to insert missing <html>?
<head></head><body></body>
<html></html>
<body></body>
<head></head>
~~<head></head><body></body>~~

## Which HTML creates element visible but excluded from tab order?
<input disabled>
<input hidden>
<input readonly>
<input type="text">
~~<input readonly>~~

## Which HTML causes malformed but rendered anchor nesting?
<a><div>X</div></a>
<a><span>X</span></a>
<div><a>X</a></div>
<a>X</a>
~~<a><div>X</div></a>~~

## Which HTML creates invalid but accepted description list?
<dl><p>X</p></dl>
<dl><dt>X</dt></dl>
<dl><dd>X</dd></dl>
<dl></dl>
~~<dl><p>X</p></dl>~~

## Which HTML causes duplicate accessibility names?
<label>Email<input></label>
<input aria-label="Email">
<label for="e">Email</label><input id="e">
<input placeholder="Email">
~~<label>Email<input></label>~~

## Which HTML hides element from layout and accessibility?
<span hidden>X</span>
<span aria-hidden="true">X</span>
<span style="display:none">X</span>
<span>X</span>
~~<span hidden>X</span>~~

## Which HTML creates invalid but displayed sectioning?
<section><p><article>X</article></p></section>
<article><p>X</p></article>
<section><h1>X</h1></section>
<div><h1>X</h1></div>
~~<section><p><article>X</article></p></section>~~

## Which HTML creates misleading progress indication?
<progress value="5"></progress>
<progress value="5" max="10"></progress>
<meter value="5" max="10"></meter>
<meter value="5"></meter>
~~<progress value="5"></progress>~~

## Which HTML makes element clickable but not focusable?
<span>X</span>
<button>X</button>
<a href="#">X</a>
<input type="submit">
~~<span>X</span>~~

## Which HTML causes browser to infer missing <caption>?
<table><tr><td>X</td></tr></table>
<table><caption>X</caption></table>
<table></table>
<table><thead></thead></table>
~~<table><tr><td>X</td></tr></table>~~

## Which HTML creates an element that is focusable but has no semantic role?
<div tabindex="0"></div>
<button></button>
<a href="#"></a>
<input type="text">
~~<div tabindex="0"></div>~~

## Which HTML causes the browser to silently close a <p> tag?
<p><div>X</div></p>
<p><span>X</span></p>
<p>X</p>
<div><p>X</p></div>
~~<p><div>X</div></p>~~

## Which HTML creates an input excluded from form submission but still editable?
<input disabled>
<input readonly>
<input hidden>
<input required>
~~<input readonly>~~

## Which HTML produces invalid but rendered nesting of sectioning content?
<p><section>X</section></p>
<section><p>X</p></section>
<article><p>X</p></article>
<div><section>X</section></div>
~~<p><section>X</section></p>~~

## Which HTML creates an element announced twice by screen readers?
<label>Name<input aria-label="Name"></label>
<label>Name<input></label>
<input aria-label="Name">
<label for="n">Name</label><input id="n">
~~<label>Name<input aria-label="Name"></label>~~

## Which HTML creates a submit control without visible UI?
<input type="submit" hidden>
<button hidden></button>
<input disabled type="submit">
<button aria-hidden="true"></button>
~~<input type="submit" hidden>~~

## Which HTML creates an invalid but clickable element?
<a><button>X</button></a>
<button><span>X</span></button>
<a href="#"><span>X</span></a>
<div><button>X</button></div>
~~<a><button>X</button></a>~~

## Which HTML causes a heading to be skipped in the outline algorithm?
<h1>A</h1><h4>B</h4>
<h1>A</h1><h2>B</h2>
<section><h1>A</h1></section>
<article><h1>A</h1></article>
~~<h1>A</h1><h4>B</h4>~~

## Which HTML creates invalid but rendered table structure?
<table><td>X</td></table>
<table><tr><td>X</td></tr></table>
<table><tbody><tr><td>X</td></tr></tbody></table>
<table></table>
~~<table><td>X</td></table>~~

## Which HTML creates a form control that cannot receive focus?
<input disabled>
<input readonly>
<input required>
<input type="text">
~~<input disabled>~~

## Which HTML creates ambiguous landmark regions?
<nav><nav>X</nav></nav>
<nav><a>X</a></nav>
<header><nav>X</nav></header>
<main><nav>X</nav></main>
~~<nav><nav>X</nav></nav>~~

## Which HTML hides content visually but keeps it accessible?
<span hidden>X</span>
<span style="display:none">X</span>
<span aria-hidden="true">X</span>
<span style="position:absolute;left:-9999px">X</span>
~~<span style="position:absolute;left:-9999px">X</span>~~

## Which HTML produces invalid but accepted nested lists?
<ul><ul><li>X</li></ul></ul>
<ul><li><ul><li>X</li></ul></li></ul>
<ol><li>X</li></ol>
<dl><dt>X</dt></dl>
~~<ul><ul><li>X</li></ul></ul>~~

## Which HTML creates a clickable element with no keyboard access?
<div onclick="x()">X</div>
<button>X</button>
<a href="#">X</a>
<input type="submit">
~~<div onclick="x()">X</div>~~

## Which HTML causes implicit generation of <li> elements?
<ul>A B C</ul>
<ul><li>A</li><li>B</li></ul>
<ol><li>A</li></ol>
<ul></ul>
~~<ul>A B C</ul>~~

## Which HTML creates a form field without an accessible name?
<input type="text">
<input aria-label="Name">
<label>Name<input></label>
<input placeholder="Name">
~~<input type="text">~~

## Which HTML causes invalid but rendered description list content?
<dl><p>X</p></dl>
<dl><dt>X</dt></dl>
<dl><dd>X</dd></dl>
<dl></dl>
~~<dl><p>X</p></dl>~~

## Which HTML causes browser to auto-close a <li> element?
<li>A<li>B<li>C
<ul><li>A</li></ul>
<ol><li>A</li></ol>
<li>A</li>
~~<li>A<li>B<li>C~~

## Which HTML creates duplicate tab stops?
<a href="#">X</a><button>X</button>
<div tabindex="0"></div><div tabindex="0"></div>
<input type="text">
<button>X</button>
~~<div tabindex="0"></div><div tabindex="0"></div>~~

## Which HTML makes an image decorative but still visible?
<img src="x.jpg" alt="">
<img src="x.jpg">
<img src="x.jpg" alt="X">
<img src="x.jpg" aria-label="X">
~~<img src="x.jpg" alt="">~~

## Which HTML causes invalid nesting repaired by browser?
<button><div>X</div></button>
<button><span>X</span></button>
<a><span>X</span></a>
<div><button>X</button></div>
~~<button><div>X</div></button>~~

## Which HTML creates a focusable element ignored by screen readers?
<div tabindex="0" aria-hidden="true"></div>
<button>X</button>
<a href="#">X</a>
<input type="text">
~~<div tabindex="0" aria-hidden="true"></div>~~

## Which HTML produces misleading semantic emphasis?
<b>Warning</b>
<strong>Warning</strong>
<em>Warning</em>
<mark>Warning</mark>
~~<b>Warning</b>~~

## Which HTML causes browser to infer missing <body>?
<p>X</p>
<body><p>X</p></body>
<html><body></body></html>
<head></head>
~~<p>X</p>~~

## Which HTML creates a form that submits empty data?
<form><input disabled></form>
<form><input readonly></form>
<form><input></form>
<form><input value="X"></form>
~~<form><input disabled></form>~~

## Which HTML creates invalid but accepted table header placement?
<table><th>X</th></table>
<table><tr><th>X</th></tr></table>
<table><thead><tr><th>X</th></tr></thead></table>
<table></table>
~~<table><th>X</th></table>~~

## Which HTML creates content outside all landmarks?
<p>X</p>
<main><p>X</p></main>
<section><p>X</p></section>
<article><p>X</p></article>
~~<p>X</p>~~

## Which HTML creates conflicting ARIA roles?
<button role="link">X</button>
<a href="#">X</a>
<button>X</button>
<div role="button">X</div>
~~<button role="link">X</button>~~

## Which HTML creates an element removed from the accessibility tree?
<div aria-hidden="true">X</div>
<div>X</div>
<div tabindex="0">X</div>
<button>X</button>
~~<div aria-hidden="true">X</div>~~

## Which HTML produces an anchor without a destination?
<a>X</a>
<a href="#">X</a>
<a href="/">X</a>
<a href="">X</a>
~~<a>X</a>~~

## Which HTML creates invisible but tabbable element?
<input style="opacity:0">
<input hidden>
<input disabled>
<input type="hidden">
~~<input style="opacity:0">~~

## Which HTML causes incorrect reading order?
<span>X</span><h1>Y</h1>
<h1>Y</h1><span>X</span>
<main><h1>Y</h1></main>
<section><h1>Y</h1></section>
~~<span>X</span><h1>Y</h1>~~

## Which HTML creates a progress bar without meaning?
<progress value="3"></progress>
<progress value="3" max="10"></progress>
<meter value="3" max="10"></meter>
<meter value="3"></meter>
~~<progress value="3"></progress>~~

## Which HTML causes a label to point to nothing?
<label for="x">Name</label><input id="y">
<label>Name<input></label>
<input aria-label="Name">
<label for="n">Name</label><input id="n">
~~<label for="x">Name</label><input id="y">~~

## Which HTML creates duplicate landmark roles?
<main><main>X</main></main>
<main><section>X</section></main>
<article><section>X</section></article>
<div><main>X</main></div>
~~<main><main>X</main></main>~~

## Which HTML creates misleading button text for screen readers?
<button>Click</button>
<button aria-label="Submit">X</button>
<button>Submit</button>
<input type="submit" value="Submit">
~~<button aria-label="Submit">X</button>~~

## Which HTML produces invalid but rendered nesting of anchors?
<a href="#"><a href="#">X</a></a>
<a href="#"><span>X</span></a>
<div><a href="#">X</a></div>
<a href="#">X</a>
~~<a href="#"><a href="#">X</a></a>~~

## Which HTML hides content but allows screen reader navigation?
<span style="visibility:hidden">X</span>
<span hidden>X</span>
<span aria-hidden="true">X</span>
<span>X</span>
~~<span style="visibility:hidden">X</span>~~

## Which HTML creates invalid but displayed figure structure?
<figure><p>X</p></figure>
<figure><img src="x.jpg"></figure>
<figure><figcaption>X</figcaption></figure>
<figure></figure>
~~<figure><p>X</p></figure>~~

## Which HTML causes browser to auto-generate missing <option>?
<select>A B</select>
<select><option>A</option></select>
<select></select>
<select><optgroup></optgroup></select>
~~<select>A B</select>~~

## Which HTML creates focus trap potential?
<div tabindex="0"></div><div tabindex="0"></div>
<button>X</button>
<a href="#">X</a>
<input type="text">
~~<div tabindex="0"></div><div tabindex="0"></div>~~

## Which HTML creates an element that submits multiple values?
<input name="x"><input name="x">
<input id="x"><input id="x">
<input value="x"><input value="y">
<input><input>
~~<input name="x"><input name="x">~~

## Which HTML causes incorrect form validation behavior?
<input disabled required>
<input required>
<input readonly required>
<input value="">
~~<input disabled required>~~

## Which HTML creates ambiguous page title for assistive tech?
<title>Home</title>
<title>Welcome</title>
<title>Page</title>
<title>Dashboard – User Settings</title>
~~<title>Page</title>~~

## Which HTML causes browser to ignore whitespace text nodes?
<ul>
<li>A</li>
</ul>
<pre> X </pre>
<p> X </p>
<div> X </div>
~~<ul>
<li>A</li>
</ul>~~

## Which HTML creates invalid but visible inline block structure?
<span><div>X</div></span>
<span><span>X</span></span>
<div><span>X</span></div>
<p><span>X</span></p>
~~<span><div>X</div></span>~~

## Which HTML makes a checkbox impossible to uncheck?
<input type="checkbox" checked disabled>
<input type="checkbox" checked>
<input type="checkbox">
<input type="radio">
~~<input type="checkbox" checked disabled>~~

## Which HTML creates misleading content order visually vs DOM?
<span>X</span><h1>Y</h1>
<h1>Y</h1><span>X</span>
<main><h1>Y</h1></main>
<section><h1>Y</h1></section>
~~<span>X</span><h1>Y</h1>~~

## Which HTML produces invalid but rendered main content?
<main><p><main>X</main></p></main>
<main><p>X</p></main>
<div><main>X</main></div>
<main></main>
~~<main><p><main>X</main></p></main>~~

## Which HTML creates maximum semantic confusion for accessibility tools?
<div role="button"><a href="#">X</a></div>
<button>X</button>
<a href="#">X</a>
<input type="submit">
~~<div role="button"><a href="#">X</a></div>~~
