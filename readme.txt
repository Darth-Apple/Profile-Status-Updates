MyBB Status Manager (in development): 

QUICK NOTE: This is a development version of this plugin. It is pre-beta. It is strongly recommended to NOT use this on live forums until the release, as we will NOT be developing an upgrade path from pre-release versions to the final version! 

Developers may be seen using this on live forums. I’ve been doing so for a long time to test and to iron out bugs and issues. However, the code that is used here is often newer, and also is being very rapidly changed over what you see on development forums. As such, it’s usually much less stable. We cannot guarantee that it is stable until we release a final release candidate, and eventually, release it on the mod site. Please be patient, it will be coming around Dec 2020!


-----------------------------------------
DEV ROADMAP: 
-----------------------------------------

This is the first beta release. This version will be highly unstable and is 
Intended to help us discover bugs. 

We will be releasing a second beta shortly that will be much safer to use on live forums. 



-----------------------------------------
ABOUT: 
-----------------------------------------


Status Manager is a fully featured profile comments/status updates plugin made to meet the modern needs of MyBB forums in 2020. It has full support for replies, likes, portal/profile support, postbit popups, and more. Additionally, it is powered by Ajax. Posting, editing, or deleting comments and replies will not generate a page refresh! 

It is intended as a replacement for various features within MyProfile to create a new plugin that is modernized and properly compatible with newer MyBB releases. 

FEATURES: 

 - Full support for replies
 - Optional support for likes
 - Built in alerts (MyAlerts integration coming soon)
 - Customizable. Uses its own stylesheets and templates. 
 - No page refresh required to post, edit, like, or delete a status. 
 - Shows up on the portal, profile, index (optional), and postbox (optional)
 - Optional “show user’s status history” on the postbit. Opens a popup when clicked. 
 - Ability to link to any individual status. 
 - “Community Wall” - a page with a history of all community status updates.  

-----------------------------------------
KNOWN ISSUES: 
-----------------------------------------

Status Manager is pre beta! Known issues will be tracked extensively on GitHub. 

 - Edit form does not work. Redirects and does not edit. 
 - Delete form does not remove status via javascript, but does delete. 
 - Native alerts sometimes act funny if another user comments on a status you've commented on. 
 - Tooltip on "show likes" sometimes acts odd. 

-----------------------------------------
FAQ: 
-----------------------------------------

- Why is there a maximum comment limit? 

	At this time, pagination does not exist within comments. To prevent an epilogue from taking up your entire screen on an endless scroll, the plugin has a max-comments feature. A future version will address this with better comment scrolling parameters and options. 

- Should I use native alerts? 

	We recommend MyAlerts if you have it installed. Otherwise, definitely leave native alerts enabled. Otherwise, users won't know if someone has commented on their wall! 



-----------------------------------------
CONTRIBUTING: 
-----------------------------------------

Please create any pull requests for contributions! Several people have expressed interest in contributing. I will give a shout out to all contributors on the official release, and look forward to collaborating on the development of this plugin. 

Some guidelines for contributions: 

 - Make sure they are secure and stable. :)

 - I've brought this project back from the dead. There are some issues that are still being fixed regarding the code itself. As such, there are bits of inline CSS and other issues that are being resolved. If you catch something and would like to fix it, please do not hesitate. It is a huge help for the final project. 

 - The initial release will focus on stability, not on additional features. A follow up release will add additional features, but the current focus is to ensure that the initial release is as stable as possible. 

 - Whatever we do, we will not edit the headerinclude template (unless we have an accompanying compatibility mode with an alternative method of loading scripts). This template is modified on most MyBB upgrades, and as such, we avoid modifying this template to prevent issues on MyBB updates. Currently, there is inline javascript, however. This is not ideal. If anyone would like to help resolve this, please feel free to contribute! Javascript is my weak point, so it's one of the most needed areas for contribution on this plugin.  

-----------------------------------------
INSTALLATION: 
-----------------------------------------

Upload the contents of the Upload directory to your forum, overwriting any files if prompted. 

-----------------------------------------
LICENSE: 
-----------------------------------------

   This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

