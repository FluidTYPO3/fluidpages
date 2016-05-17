# Fluidpages Change log

3.6.0 - 2016-05-17
------------------

- [Prevention of fatal PHP errors on incomplete configurations](https://github.com/FluidTYPO3/fluidpages/commit/79bc7b55334f9849b5dcb676958fe6bccc066b64)
- [Bug fix for page overlays without data, fix for enableFields in overlays](https://github.com/FluidTYPO3/fluidpages/commit/89131d14cd95bc530e3c9da52d99abe1e6f4f410)
- [#318](https://github.com/FluidTYPO3/fluidpages/pull/318) Bug fix for inheritance loop processing
- [#315](https://github.com/FluidTYPO3/fluidpages/pull/315) Bug fix for template icons resolved by template identifier
- [#314](https://github.com/FluidTYPO3/fluidpages/pull/314) Perform label translation (required since https://github.com/FluidTYPO3/flux/commit/81bb392f99ab1e30c30303ee5d885a7a44c9ff6f / Flux 7.4.0).

3.5.0 - 2016-03-03
------------------

- [#310](https://github.com/FluidTYPO3/fluidpages/pull/310) Bug fix for incorrect variable name used in icon resolving

- [#306](https://github.com/FluidTYPO3/fluidpages/pull/306) Bug fix for showing the correct template's configuration 

- [#305](https://github.com/FluidTYPO3/fluidpages/pull/305) Bug fix for rendering when no layout is selected

- [#298](https://github.com/FluidTYPO3/fluidpages/pull/299) PHP7 compatibility
  - [#301](https://github.com/FluidTYPO3/fluidpages/pull/301) PHP7 compatibility
  - [#300](https://github.com/FluidTYPO3/fluidpages/pull/300) PHP7 compatibility

- [#296](https://github.com/FluidTYPO3/fluidpages/pull/296) Page configuration fields now possible to add to language overlay.
  - Extension configuration contains toggle to enable adding mentioned fields to translatable fields. Implemented as substitute for
    the translation behavior of FlexForms which has been removed in TYPO3.
- [#297](https://github.com/FluidTYPO3/fluidpages/pull/297) Performance improvement for PageProvider / SubPageProvider
  - [#298](https://github.com/FluidTYPO3/fluidpages/pull/298) Followup performance improvement
  
- [#293](https://github.com/FluidTYPO3/fluidpages/pull/293) Page templates' `Preview` section now shown in "page" module.

3.4.0 - 2015-09-21
------------------

- :exclamation: Support of [TYPO3 6.2 dropped](https://github.com/FluidTYPO3/fluidpages/commit/b225773cd2bfe8b51e148178e26a9da36d44cdac)
	- For TYPO3 6.2 based projects there is a [*legacy*](https://github.com/FluidTYPO3/fluidpages/tree/legacy) branch

- :exclamation: PHP 5.5 is [minimum required](https://github.com/FluidTYPO3/fluidpages/commit/cca22bbafad49a9cce9ae5cf7c3b6a23e8291d40)

- :exclamation: [#278](https://github.com/FluidTYPO3/fluidpages/pull/278) Allow raw content be put into <head> section of a template
	- New section `HeaderCode` should be used in your page templates for this
	- Beware, that if you used such section name for your own purposes, you need to rename it 

- *'enabled'* flux form attribute is respected, when building list of available page templates
	- [Source commit with more info](https://github.com/FluidTYPO3/fluidpages/commit/32d4765aaad8df2f1516b0bb93cc8956f66c1f36)

- [#284](https://github.com/FluidTYPO3/fluidpages/pull/284) Fixed issue with non-respected overloads of `templateRootPaths`

- [#281](https://github.com/FluidTYPO3/fluidpages/pull/281) TCA element browser wizard for RawDoktype aligned with TYPO3 7

- [#279](https://github.com/FluidTYPO3/fluidpages/pull/279) Avoid error `is not a valid template resource URI ...Resources/Private/Templates/Page/.`
 
3.3.1 - 2015-08-08
------------------

- No important changes

3.3.0 - 2015-08-08
------------------

- Support of TYPO3 7.4 added

- Support of upcoming Flux 7.3 added

- [#261](https://github.com/FluidTYPO3/fluidpages/pull/261) Multi-domain installations can rely on static TS inclusion of provider extension
	- In other words: no more output of any page layouts on those page-tree branches, where no TS from provider extension included

- [#259](https://github.com/FluidTYPO3/fluidpages/pull/259) `plugin.tx_fluidpages.siteRootInheritance = 0` also affects sub-pages selector now


3.2.3 - 2015-05-20
------------------

- [#257](https://github.com/FluidTYPO3/fluidpages/pull/257) Runtime cache for inherited values implemented, which speeds up BE and FE page load on deep pages of a page tree

3.2.2 - 2015-04-26
------------------

- Bugfix for subpage configuration always being used
  - [Source commit with more info](https://github.com/FluidTYPO3/fluidpages/commit/66ad2bfc1bd42f4c377cec39829d77d5fef601c7)

3.2.1 - 2015-04-23
------------------

- [#252](https://github.com/FluidTYPO3/fluidpages/pull/252) Bugfix for incorrectly resolved main/sub provider and template resulting in use of wrong page configuration and broken inheritance

- [#241](https://github.com/FluidTYPO3/fluidpages/pull/241) Bugfix for saving incomplete page records when working in the page tree or access module

3.2.0 - 2015-03-18
------------------

- :exclamation: Legacy TYPO3 support removed and dependencies updated
  - TYPO3 6.2 is minimum required
  - TYPO3 7.1 is supported
  - Flux 7.2 is minimum required
  - ClassAliasMap removed - switch to the proper vendor and namespace

- :exclamation: Legacy support for TS registration removed
  - `plugin.tx_fluidpages.collections.` support removed
  - `plugin.tx_fed.page.` support removed
  - [Source commit with more info](https://github.com/FluidTYPO3/fluidpages/commit/b5fd17bd69315589ea77a77202fc5eb0255cf0f1)

- :exclamation: `\FluidTYPO3\Fluidpages\Controller\AbstractPageController` deprecated
  - Extend `\FluidTYPO3\Fluidpages\Controller\PageController` instead
  - [Source commit with more info](https://github.com/FluidTYPO3/fluidpages/commit/5da5439a161b880b7db11dcffa369944d0766787)

- [#226](https://github.com/FluidTYPO3/fluidpages/pull/226) Possible to use *'templateRootPaths'* (plural) option from TYPO3 6.2 to overload template paths
  - `plugin.tx_yourext.view.templateRootPaths` syntax is supported
  - *'templateRootPath'* (singular) and *'overlays'* are deprecated
  - [FluidTYPO3/flux#758](https://github.com/FluidTYPO3/flux/pull/758) - source feature

- [#208](https://github.com/FluidTYPO3/fluidpages/pull/208) Template icon can be autoloaded, based on name convention
  - Template *EXT:extensionKey/Resources/Private/Templates/$controller/$templateName.html* loads an icon from *EXT:extensionKey/Resources/Public/Icons/$controller/$templateName.(png|gif)*
  - Icon can be set manually via option attribute as before
  - [FluidTYPO3/flux#687](https://github.com/FluidTYPO3/flux/pull/687) - source feature

- Unassigned content areas are supported
  - Content area without *'colPos'* key is marked as 'unassigned'
  - TYPO3 Displays is as shaded area with no editing
  - Possible use-case: programatically created content, which shouldn't be editable, but must be visible for editors
  - [Source commit with more info](https://github.com/FluidTYPO3/fluidpages/commit/6c92d8a3844337476613c7da429cd88ec2f13a58)

- [#229](https://github.com/FluidTYPO3/fluidpages/pull/229) Inheritance support assimilated from Flux
  - [FluidTYPO3/flux#760](https://github.com/FluidTYPO3/flux/pull/760) - source feature

- Truncating of inherited values fixed
  - The problem was in persisting inherited FlexForm values in child pages, when saving them - this made no possibility to change inherited value by only changing it in parent page
  - [FluidTYPO3/flux#712](https://github.com/FluidTYPO3/flux/pull/712) - detailed description and discussion of this issue
