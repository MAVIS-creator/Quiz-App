# Group 1

## Which CSS property creates a new stacking context without affecting layout?
position: relative
z-index: 1
opacity: 0.99
transform: none
~~opacity: 0.99~~

## What happens when you apply margin: auto to an absolutely positioned element with left: 0 right: 0?
It centers horizontally
It becomes invalid
It moves to top-left
It creates a stacking context
~~It centers horizontally~~

## Which CSS selector has the highest specificity?
.class
#id
div.class
[attribute]
~~#id~~

## What is the specificity value of ul > li.active?
(0,2,1)
(0,1,2)
(1,1,1)
(0,1,1)
~~(0,1,2)~~

## Which CSS rule breaks the cascade?
!important
@media
@import
@keyframes
~~!important~~

## What does the CSS contain property do?
Creates a containment boundary
Defines element containment
Enables layout/paint/style containment
All of above
~~All of above~~

## Which CSS property enables hardware acceleration?
transform: translate3d(0,0,0)
will-change: transform
backface-visibility: hidden
All of above
~~All of above~~

## What is the z-index of a stacking context root?
auto
0
undefined
Always higher than children
~~undefined~~

## Which CSS Grid property creates implicit grid tracks?
grid-template-columns
grid-auto-columns
grid-column-start
grid-gap
~~grid-auto-columns~~

## What happens with margin collapse in a flex container?
Margins collapse normally
Margins never collapse
Only vertical margins collapse
Horizontal margins collapse
~~Margins never collapse~~

## Which pseudo-element is not valid in CSS?
::before
::after
::first-line
::before-content
~~::before-content~~

## What is the stack order of positioned elements with same z-index?
Document order (later on top)
Earlier in document (on top)
Determined by specificity
Random
~~Document order (later on top)~~

## How many CSS variables can be nested?
1
5
10
Unlimited
~~Unlimited~~

## Which CSS function creates a flexible length?
calc()
minmax()
clamp()
fit-content()
~~clamp()~~

## What does CSS subgrid do?
Creates a new grid
Aligns to parent grid
Nests grid properties
Inherits parent grid tracks
~~Inherits parent grid tracks~~

## Which property does NOT trigger layout recalculation?
width
opacity
background-color
height
~~opacity~~

## What is the difference between will-change: opacity and transform?
No difference
Transform is hardware accelerated
Opacity is hardware accelerated
Both are always GPU accelerated
~~Transform is hardware accelerated~~

## Which CSS selector matches siblings?
+ (adjacent sibling)
> (child)
~ (general sibling)
Both + and ~
~~Both + and ~~

## What does currentColor represent in CSS?
Current element color
Inherited color
Computed color value
Current text color
~~Current text color~~

## How does CSS Grid handle implicit rows?
grid-auto-rows sets height
Uses content height
Collapses to zero
Creates 1fr rows
~~grid-auto-rows sets height~~

## Which CSS property prevents text wrapping?
white-space: nowrap
word-wrap: break-word
word-break: break-all
text-wrap: nowrap
~~white-space: nowrap~~

## What does flex: 1 actually expand to?
flex-grow: 1
flex-grow: 1; flex-shrink: 1; flex-basis: 0
flex: 1 1 auto
flex-grow: 1; flex-basis: 0
~~flex-grow: 1; flex-shrink: 1; flex-basis: 0~~

## Which pseudo-class is not supported on all elements?
:hover
:active
:visited
:checked
~~:visited~~

## How does CSS calc() handle unit conversions?
Automatic conversion
No conversion allowed
Requires same units
Context-dependent
~~No conversion allowed~~

## What is the initial value of position property?
static
relative
absolute
auto
~~static~~

## Which CSS property creates an IFC (Inline Formatting Context)?
display: inline
display: block
display: inline-block
display: flex
~~display: inline-block~~

## What does overflow: auto do if no overflow exists?
Shows scrollbar
Hides scrollbar
Conditional scrollbar
Always scrollbar
~~Conditional scrollbar~~

## How does CSS handle negative z-index?
Lower than static elements
Never renders
Renders behind elements
Same as 0
~~Lower than static elements~~

## Which property affects text rendering performance?
text-rendering: optimizeSpeed
font-smoothing
-webkit-font-smoothing
All above
~~All above~~

## What happens with margin on inline elements?
Horizontal margins apply
Both margins apply
No margins apply
Vertical margins apply
~~Horizontal margins apply~~

## How does flex-basis work with width?
Overrides width
Width overrides flex-basis
They work together
Depends on flex-direction
~~Depends on flex-direction~~

## Which CSS property creates a new BFC (Block Formatting Context)?
overflow: hidden
float: left
position: absolute
display: flow-root
~~display: flow-root~~

## What does CSS clip-path create?
Visual clipping
Rendering optimizations
Both visual and layout clipping
Pointer events clipping
~~Visual clipping~~

## How are CSS custom properties inherited?
Always inherited
Never inherited
Like normal properties
Only within scope
~~Like normal properties~~

## What is the computed value of 1em in a nested context?
Root font size
Parent font size
Current font size
Relative to em
~~Parent font size~~

## Which CSS value requires a calc() to work?
Mixed units
Percentages
Relative units
All above
~~Mixed units~~

## How does CSS Grid gap differ from margin?
Gap collapses
Margin collapses
Both collapse
Neither collapses
~~Neither collapses~~

## What does CSS content: "" do on pseudo-elements?
Removes content
Adds empty space
Creates zero-width element
Invalid CSS
~~Adds empty space~~

## Which HTML attribute is NOT reflected as CSS?
data-*
aria-*
role
style
~~data-*~~

## How does CSS handle duplicate selectors?
Last one wins
First one wins
Both apply
Specificity wins
~~Last one wins~~

## What is the stack order of floated elements?
Above inline elements
Below inline elements
Same as inline
Depends on z-index
~~Above inline elements~~

## How does text-indent affect text layout?
Indents first line
Indents all lines
Indents with percentage
Negative values move left
~~Indents first line~~

## Which CSS property prevents margin collapse?
border
padding
overflow: hidden
All above
~~All above~~

## What does CSS :not() selector accept?
Single selector
Multiple selectors
Complex selectors
Simple selectors only
~~Simple selectors only~~

## How does background-attachment: fixed work?
Fixes to viewport
Fixes to element
Fixes to parent
Depends on position
~~Fixes to viewport~~

## What is the minimum width of a flex item?
auto
0
content
min-content
~~auto~~

## Which CSS color format is most performant?
Named colors
Hex colors
RGB
HSL
~~Named colors~~

## How does CSS Grid handle fractional units with gaps?
Gap reduces available space
Gap calculated separately
Gap ignored
Multiplied by gap
~~Gap reduces available space~~

## What does CSS mask property do?
Hides elements
Creates transparency
Clips element shape
Creates visual mask
~~Creates transparency~~

## How are CSS @media queries evaluated?
At parse time
At render time
Continuously
On layout
~~Continuously~~

## Which CSS property creates a containing block?
position: relative
transform: translate(0)
opacity: 0.9
All above
~~All above~~

## What does backdrop-filter require?
Transparent background
A backdrop element
CSS support
Position: fixed
~~A backdrop element~~

## How does CSS mix-blend-mode affect layering?
Changes blending
Changes z-order
Changes opacity
Changes composite
~~Changes composite~~

## What is the default flex-wrap value?
nowrap
wrap
wrap-reverse
auto
~~nowrap~~

## Which pseudo-element can be styled with width?
::before
::after
::first-line
::selection
~~::before~~

## How does CSS transform affect layout flow?
Removes from flow
Affects layout
Changes rendering layer
Doesn't affect layout
~~Doesn't affect layout~~

## What does vertical-align do on inline-block?
Aligns to baseline
Aligns to top
Aligns to middle
Context-dependent
~~Aligns to baseline~~

## Which CSS value is keyword not computed?
inherit
initial
unset
revert
~~All are keywords~~

## How does CSS border-image work with border-radius?
Works together
Border-radius ignored
Border-image ignored
Context-dependent
~~Border-image ignored~~

## What does CSS :is() selector do with specificity?
Takes highest specificity
Ignores specificity
Takes :is() specificity
Adds specificity
~~Takes highest specificity~~

## How does line-height with percentage work?
Relative to font-size
Relative to element width
Relative to parent
Absolute value
~~Relative to font-size~~

## Which CSS property enables subpixel rendering?
-webkit-text-stroke
text-rendering
font-smoothing
-webkit-font-smoothing
~~-webkit-font-smoothing~~

## What does CSS paint order affect?
SVG rendering order
Element rendering
Stacking layers
Visual output
~~SVG rendering order~~

## How does CSS counter() function work?
Automatic numbering
Manual numbering
Page counting
Section counting
~~Automatic numbering~~

## What is the difference between auto and stretch in CSS Grid?
auto respects content
stretch fills available space
Both fill available space
No difference
~~stretch fills available space~~

## Which CSS property prevents element reflow?
contain: layout
contain: paint
contain: style
All above
~~contain: layout~~

## How does CSS :focus-visible work differently than :focus?
Shows focus ring based on input method
Always shows focus ring
Never shows focus ring
Same as :focus
~~Shows focus ring based on input method~~

## What does CSS writing-mode affect?
Text direction
Block flow direction
Inline direction
All above
~~All above~~

## How does CSS scroll-snap-type work with snap-align?
snap-type defines container behavior
snap-align defines point
Both work together
snap-type overrides snap-align
~~Both work together~~

## Which CSS filter does NOT affect rendering?
blur()
brightness()
drop-shadow()
invert()
~~All affect rendering~~

## What does CSS scroll-behavior do?
Enables smooth scrolling
Disables smooth scrolling
Instant scroll
Jump scroll
~~Enables smooth scrolling~~

## How does CSS place-items shorthand work?
align-items justify-items
justify-items align-items
Same as both
Only in Grid
~~align-items justify-items~~

## What is the default value of object-fit?
fill
contain
cover
scale-down
~~fill~~

## Which CSS property creates scroll anchoring?
scroll-anchor-align
overflow-anchor: auto
scroll-behavior
anchor()
~~overflow-anchor: auto~~

## How does CSS font-variant-numeric work?
Applies number styling
Context-dependent
Requires font support
All above
~~Requires font support~~

## What does CSS text-decoration-skip do?
Skips decoration on text
Skips ink rendering
Controls what to skip
Disables underline
~~Controls what to skip~~

## How does CSS column-span work across columns?
Spans all columns
Spans current column
Context-dependent
Ignored in flex
~~Spans all columns~~

## Which CSS property affects touch target size?
touch-action
pointer-events
hit-test-order
target-size
~~touch-action~~

## How does CSS letter-spacing work with text?
Adds space between letters
Reduces space
Percentage-based
Fixed units only
~~Adds space between letters~~

## What does CSS text-orientation do?
Rotates text
Changes text direction
Mixed orientation
Set glyph orientation
~~Set glyph orientation~~

## How does CSS accent-color affect form elements?
Changes button color
Changes form colors
Changes input accent
All above
~~All above~~

## Which CSS property prevents content shift?
scrollbar-gutter
overflow: scroll
overflow-y: scroll
scroll-padding
~~scrollbar-gutter~~

## How does CSS forced-color-adjust work?
Forces colors
Prevents color forcing
Adjusts colors
Context-dependent
~~Prevents color forcing~~

## What does CSS appearance: none do?
Removes styling
Removes all styles
Removes default styles
Context-dependent
~~Removes default styles~~

## How does CSS @layer work with cascade?
Defines layer order
Changes specificity
Affects !important
All above
~~All above~~

## Which CSS property creates text outline?
text-stroke
text-outline
-webkit-text-stroke
outline
~~-webkit-text-stroke~~

## How does CSS inset property work?
Shorthand for top/right/bottom/left
Only for absolute positioning
Sets all positions
Works with relative too
~~Shorthand for top/right/bottom/left~~

## What does CSS aspect-ratio do?
Maintains width/height ratio
Locks element size
Responsive sizing
Prevents distortion
~~Maintains width/height ratio~~

## How does CSS matrix() transform work?
3D transformation
2D transformation
Matrix multiplication
Complex positioning
~~2D transformation~~

## Which CSS property affects focus management?
outline
outline-offset
outline-width
outline-color
~~outline-offset~~

## How does CSS :target pseudo-class work?
Targets anchor link
Targets focused element
Targets hovered element
Targets active element
~~Targets anchor link~~

## What does CSS scroll-margin do?
Adds scroll offset
Adds margin to scroll
Prevents scroll overlap
Creates scroll space
~~Adds scroll offset~~

## How does CSS white-space: pre-wrap work?
Preserves whitespace and wraps
Preserves no wrap
Collapses whitespace
Preserves whitespace only
~~Preserves whitespace and wraps~~

## Which CSS property creates perspective?
perspective property
transform: perspective()
Both work
context-3d
~~Both work~~

## How does CSS backdrop-filter performance impact?
GPU intensive
CPU intensive
Minimal impact
Context-dependent
~~GPU intensive~~

## What does CSS clip property do?
Clips to shape
Clips to rectangle
Deprecated
Clips visual content
~~Clips to rectangle~~

## How does CSS scroll-padding-top work?
Adds top scroll margin
Prevents scroll overlap
Sets scroll position
Adjusts viewport
~~Adds top scroll margin~~

## Which CSS property affects print layout?
page-break-before
break-before
Both work
orphans
~~Both work~~

## How does CSS word-spacing work with justification?
Adjusts word space
Fixed space
Relative space
Ignored in justify
~~Adjusts word space~~

## What does CSS unicode-bidi do?
Controls text direction
Overrides direction
Explicit direction control
Parent control
~~Explicit direction control~~

## How does CSS overflow-wrap differ from word-break?
Breaks at word boundaries
Breaks anywhere
Preference order
No difference
~~Breaks at word boundaries~~

## Which CSS property controls hyphenation?
hyphens
word-break
hyphenate
hyphen-char
~~hyphens~~

## How does CSS ruby-align work?
Aligns ruby text
Text alignment
Ruby positioning
Base alignment
~~Aligns ruby text~~

## What does CSS marks property do?
Printing marks
Crop marks
Registration marks
All above
~~All above~~

## How does CSS size property work in print?
Sets page size
Sets element size
Print dimensions
Page orientation
~~Sets page size~~

## Which CSS property prevents text selection?
user-select: none
cursor: default
pointer-events: none
text-decoration: none
~~user-select: none~~

## How does CSS caret-color affect input?
Changes cursor color
Changes text color
Changes highlight color
Changes input border
~~Changes cursor color~~

## What does CSS outline-offset do?
Offsets outline from border
Moves outline
Creates space
Adjusts outline position
~~Offsets outline from border~~

## How does CSS border-collapse work?
Collapses table borders
Separates borders
Context-dependent
Ignored in flex
~~Collapses table borders~~

## Which CSS property prevents text wrapping on punctuation?
word-spacing: 0
white-space: nowrap
text-wrap: balance
hanging-punctuation
~~hanging-punctuation~~

## How does CSS all property work?
Resets all properties
Inherits all properties
Both reset and inherit
Depends on value
~~Depends on value~~

## What does CSS transition property animate?
Specified properties
All properties
No properties by default
Animation dependent
~~Specified properties~~

## How does CSS animation-fill-mode work?
Fills before/after animation
Holds animation state
Both before and after
Depends on direction
~~Both before and after~~

## Which CSS property affects animation timing?
animation-timing-function
animation-duration
animation-delay
All above
~~animation-timing-function~~

## How does CSS cubic-bezier() work?
Custom easing function
Linear progression
Predefined easing
Curve interpolation
~~Custom easing function~~

## What does CSS steps() timing function do?
Creates step animation
Linear steps
Frame-based animation
All above
~~Frame-based animation~~

## How does CSS animation-direction: alternate work?
Reverses on second iteration
Plays forward only
Bounces back
Repeats direction
~~Reverses on second iteration~~

## Which CSS property creates motion blur?
filter: blur()
motion-blur
animation-blur
transform-blur
~~filter: blur()~~

## How does CSS filter: drop-shadow() differ from box-shadow?
Follows element shape
Rectangular shadow
Context-dependent
No difference
~~Follows element shape~~

## What does CSS filter: saturate() do?
Increases color saturation
Decreases saturation
Removes color
Adjusts hue
~~Increases color saturation~~

## How does CSS filter: grayscale() work?
Removes color
Reduces saturation
Desaturates image
Converts to gray
~~Desaturates image~~

## Which CSS filter affects brightness?
filter: brightness()
filter: lightness()
filter: contrast()
filter: light()
~~filter: brightness()~~

## How does CSS filter: contrast() affect image?
Increases contrast
Decreases contrast
Both possible
Adjusts colors
~~Both possible~~

## What does CSS filter: invert() do?
Inverts colors
Inverts light/dark
Creates negative
All above
~~Creates negative~~

## How does CSS filter: sepia() work?
Adds sepia tone
Removes color
Vintage effect
All above
~~All above~~

## Which CSS filter affects hue?
filter: hue-rotate()
filter: saturate()
filter: brightness()
filter: contrast()
~~filter: hue-rotate()~~

## How does CSS filter: opacity() work?
Applies transparency
Multiplies opacity
Both effects
Context-dependent
~~Multiplies opacity~~

## What does CSS text-shadow do on text?
Adds shadow effect
Affects text color
Changes text weight
Creates outline
~~Adds shadow effect~~

## How does CSS box-shadow stack with multiple values?
All shadows appear
Last shadow only
Shadows blend
Shadows overlap
~~All shadows appear~~

## Which CSS property affects shadow rendering?
filter: drop-shadow()
box-shadow
text-shadow
All create shadows
~~All create shadows~~

## How does CSS shadow-offset work?
Horizontal and vertical offset
Blur offset
Color offset
Spread offset
~~Horizontal and vertical offset~~

## What does CSS inset keyword do in box-shadow?
Creates inset shadow
External shadow
Both types
Inverts shadow
~~Creates inset shadow~~

## How does CSS spread-radius in box-shadow work?
Expands shadow
Contracts shadow
Both directions
Adds space
~~Both directions~~

## Which HTML element is semantically empty?
<br>
<hr>
<div>
<span>
~~<br>~~

## How does HTML <picture> element work with images?
Responsive images
Multiple sources
Media queries
All above
~~All above~~

## What is the purpose of HTML <source> element?
Defines media source
Specifies image source
Multiple sources
Video/audio source
~~Video/audio source~~

## How does HTML srcset attribute work?
Device pixel ratio
Image resolution
Viewport width
Multiple conditions
~~Multiple conditions~~

## What does HTML sizes attribute do?
Sets element size
Responsive sizing
Display size hints
Device independent
~~Display size hints~~

## Which HTML element creates interactive disclosure?
<details>
<summary>
<dialog>
<aside>
~~<details>~~

## How does HTML <slot> work in Web Components?
Named placeholder
Content distribution
Shadow DOM slot
Component slot
~~Content distribution~~

## What is the purpose of HTML <template> element?
Inert HTML template
Cloned content
Not parsed initially
All above
~~All above~~

## How does HTML data attribute work?
Stores custom data
Machine-readable data
Human-readable display
Both storage and display
~~Both storage and display~~

## What does HTML <dialog> element do?
Creates dialog box
Modal dialog
Modeless dialog
Context-dependent
~~Creates dialog box~~

## How does HTML <form> novalidate attribute work?
Disables validation
Skips validation
Allows invalid submit
All above
~~All above~~

## Which HTML form attribute prevents submission?
disabled
readonly
required
aria-disabled
~~disabled~~

## How does HTML pattern attribute work?
Regex validation
Text pattern
Format pattern
Character pattern
~~Regex validation~~

## What does HTML <input type="range"> create?
Slider control
Range input
Number selector
Value selector
~~Slider control~~

## How does HTML <input type="color"> work?
Color picker
Color input
Hex color
RGB input
~~Color picker~~

## Which HTML input type validates email?
type="email"
type="text"
pattern with email
type="mail"
~~type="email"~~

## How does HTML <textarea> differ from <input>?
Multi-line vs single
Size attributes
Max-length
All above
~~All above~~

## What does HTML <fieldset> do?
Groups form elements
Creates legend
Disables group
All above
~~Groups form elements~~

## How does HTML <optgroup> work in select?
Groups options
Creates categories
Disabled group
All above
~~Groups options~~

## Which HTML element creates dropdown menu?
<select>
<datalist>
<menu>
<dropdown>
~~<select>~~

## How does HTML <datalist> element work?
Suggests values
Provides options
Auto-complete list
All above
~~All above~~

## What does HTML disabled attribute do?
Disables interaction
Grays out element
Excludes from form submission
All above
~~All above~~

## How does HTML readonly attribute work?
Prevents editing
Prevents selection
Includes in submission
Editable still
~~Prevents editing~~

## Which HTML attribute provides autocomplete?
autocomplete
list
datalist
suggestions
~~autocomplete~~

## How does HTML contenteditable work?
Makes element editable
Editable text
User editing
All above
~~All above~~

## What does HTML spellcheck attribute do?
Enables spell check
Disables spell check
Browser dependent
Optional checking
~~Browser dependent~~

## How does HTML draggable attribute work?
Makes element draggable
Drag functionality
Draggable state
Browser dependent
~~Makes element draggable~~

## Which HTML event fires on drag?
ondrag
ondrop
ondragend
All above
~~All above~~

## How does HTML <output> element work?
Shows computation result
Displays value
Form output
All above
~~All above~~

## What does HTML <meter> element represent?
Scalar measurement
Progress indicator
Gauge display
Measurement range
~~Scalar measurement~~

## How does HTML <progress> differ from <meter>?
Progress shows completion
Meter shows measurement
Both similar
Context-dependent
~~Progress shows completion~~

## Which HTML attribute specifies form submission method?
method
action
enctype
target
~~method~~

## How does HTML enctype attribute work?
Encoding type
Form encoding
Submission encoding
Data format
~~Form encoding~~

## What does HTML <label> element do?
Associates text with input
Input label
Clickable label
Accessibility label
~~Associates text with input~~

## How does HTML <legend> work in forms?
Labels fieldset
Group legend
Caption text
All above
~~Labels fieldset~~

## Which HTML element creates list structure?
<ul>, <ol>, <li>
<list>
<collection>
<items>
~~<ul>, <ol>, <li>~~

## How does HTML <dl> work?
Definition list
Glossary list
Term-definition pairs
All above
~~Term-definition pairs~~

## What does HTML <dt> element represent?
Definition term
Term definition
List term
Dictionary term
~~Definition term~~

## How does HTML <dd> element work?
Definition description
Description item
Details definition
Indented definition
~~Definition description~~

## Which HTML element creates table header?
<thead>
<th>
<th scope="col">
All above
~~All above~~

## How does HTML <tbody> work in tables?
Groups table rows
Body content
Main table content
All above
~~Groups table rows~~

## What does HTML <tfoot> element do?
Table footer
Summary row
Footer group
All above
~~Footer group~~

## How does HTML scope attribute work on <th>?
Specifies header scope
Column header
Row header
Both directions
~~Specifies header scope~~

## Which HTML element creates table caption?
<caption>
<title>
<heading>
<desc>
~~<caption>~~

## How does HTML colspan attribute work?
Spans multiple columns
Column span count
Merged cells
All above
~~Spans multiple columns~~

## What does HTML rowspan attribute do?
Spans multiple rows
Row span count
Merged cells
All above
~~Spans multiple rows~~

## Which HTML attribute affects keyboard navigation?
tabindex
accesskey
tabindex and accesskey
autofocus
~~tabindex and accesskey~~

## How does HTML autofocus attribute work?
Auto focuses element
Sets focus on load
Initial focus
Browser dependent
~~Sets focus on load~~

## What does HTML <aside> element represent?
Sidebar content
Related content
Tangential content
All above
~~Tangential content~~

## How does HTML <section> differ from <div>?
Semantic grouping
No semantic meaning
Layout grouping
All above
~~Semantic grouping~~

## Which HTML element creates article content?
<article>
<main>
<section>
<content>
~~<article>~~

## How does HTML <nav> element work?
Navigation landmark
Navigation menu
Link group
All above
~~Navigation landmark~~

## What does HTML <header> represent?
Page header
Section header
Introductory content
All above
~~All above~~

## How does HTML <footer> work?
Page footer
Section footer
Contact information
All above
~~All above~~

## Which HTML element creates main content?
<main>
<content>
<primary>
<body>
~~<main>~~

## How does HTML <time> element work?
Machine-readable time
Display time
Datetime attribute
All above
~~All above~~

## What does HTML <mark> element highlight?
Highlights text
Marks important
Highlights relevance
All above
~~Highlights text~~

## How does HTML <small> differ from styling?
Semantic small text
Just styling
Legal text
All above
~~Semantic small text~~

## Which HTML element indicates strong importance?
<strong>
<em>
<b>
<i>
~~<strong>~~

## How does HTML <em> work semantically?
Emphasis
Stress emphasis
Italic emphasis
Context emphasis
~~Stress emphasis~~

## What does HTML <code> element represent?
Code snippet
Programming code
Inline code
All above
~~Inline code~~

## How does HTML <kbd> element work?
Keyboard input
User input representation
Key press
All above
~~User input representation~~

## Which HTML element displays sample output?
<samp>
<output>
<result>
<sample>
~~<samp>~~

## How does HTML <var> element work?
Variable name
Mathematical variable
Variable representation
All above
~~Variable representation~~

## What does HTML <sup> element do?
Superscript
Exponent text
Formula notation
All above
~~Superscript~~

## How does HTML <sub> work?
Subscript
Subscript text
Formula notation
All above
~~Subscript~~

## Which HTML element creates abbreviation?
<abbr>
<acronym>
<abbreviation>
<short>
~~<abbr>~~

## How does HTML <cite> element work?
Cites source
Citation reference
Work title
All above
~~Work title~~

## What does HTML <blockquote> represent?
Block quotation
Long quote
Indented quote
All above
~~Block quotation~~

## How does HTML <q> differ from <blockquote>?
Inline vs block
Short vs long
Both quote types
Context-dependent
~~Inline vs block~~

## Which HTML element creates definition?
<dfn>
<definition>
<define>
<term>
~~<dfn>~~

## How does HTML <address> work?
Contact information
Address information
Author contact
All above
~~Contact information~~

## What does HTML <pre> element preserve?
Whitespace
Formatting
Line breaks
All above
~~All above~~

## How does HTML <wbr> work?
Word break opportunity
Soft line break
Wrap point
All above
~~Word break opportunity~~

## Which HTML element embeds external content?
<iframe>
<embed>
<object>
All above
~~All above~~

## How does HTML <embed> work?
Embeds plugin content
External plugin
Embedded object
All above
~~Embeds plugin content~~

## What does HTML <object> element do?
Generic embedded object
Plugin object
Fallback content
All above
~~Generic embedded object~~

## How does HTML <param> work with objects?
Parameter for object
Plugin parameter
Configuration parameter
All above
~~Parameter for object~~

## Which HTML attribute sets character encoding?
charset
encoding
text-encoding
meta-charset
~~charset~~

## How does HTML viewport meta tag work?
Sets viewport size
Responsive viewport
Initial scale
All above
~~All above~~

## What does HTML <base> element do?
Base URL
Relative path base
Link base
All above
~~Relative path base~~

## How does HTML <link> element work?
Links resources
External resource
Style linking
All above
~~All above~~

## Which HTML element sets favicon?
<link rel="icon">
<link rel="favicon">
<icon>
<favicon>
~~<link rel="icon">~~

## How does HTML rel attribute work?
Relationship type
Link type
Resource type
Connection type
~~Relationship type~~

## What does HTML <style> scoped attribute do?
Scopes CSS to element
Local styles
Component styles
All above
~~Scopes CSS to element~~

## How does HTML charset attribute work?
Sets character encoding
Document encoding
Meta encoding
All above
~~Sets character encoding~~

## Which HTML attribute prevents frame access?
sandbox
frameborder
allow
permission
~~sandbox~~

## How does HTML loading attribute work?
Lazy loading
Resource loading
Script loading
All above
~~Lazy loading~~

## What does HTML decoding attribute do?
Image decoding
Decoding method
Async decoding
All above
~~Decoding method~~

## How does HTML async script attribute work?
Loads asynchronously
Non-blocking load
Parallel loading
All above
~~Non-blocking load~~

## Which HTML script attribute ensures order?
defer
async
order
sequential
~~defer~~

## How does HTML <noscript> work?
Fallback when JS disabled
No script content
Alternative content
All above
~~Fallback when JS disabled~~

## What does HTML <canvas> element create?
Drawing surface
Graphics container
Bitmap canvas
All above
~~Drawing surface~~

## How does HTML <svg> differ from <canvas>?
Vector vs raster
Scalable vs fixed
DOM vs bitmap
All above
~~Vector vs raster~~

## Which HTML element creates image map?
<map>
<area>
<usemap>
All above
~~All above~~

## How does HTML <track> work with video?
Provides captions
Subtitle tracks
Cue tracks
All above
~~Provides captions~~

## What does HTML kind attribute do on track?
Specifies track type
Caption type
Subtitle type
All above
~~Specifies track type~~

## How does HTML crossorigin attribute work?
Cross-origin resources
CORS policy
Authentication headers
All above
~~CORS policy~~

## Which HTML element provides fallback content?
<noscript>
<fallback>
<alternative>
<backup>
~~<noscript>~~

## How does HTML <keygen> work?
Generates key pair
Public key generation
Certificate request
Deprecated element
~~Deprecated element~~

## What does HTML <command> element do?
Menu command
UI command
Context menu
All above
~~Deprecated - use <button>~~
