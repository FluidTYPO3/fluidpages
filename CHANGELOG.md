# Fluidpages Change log

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

- :exclamation: `\FluidTYPO3\Fluidpages\Controller\AbstractPageController` deprectaed
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
