"use strict";(self["webpackChunkcontrol_center"]=self["webpackChunkcontrol_center"]||[]).push([[521],{8990:(e,t,n)=>{n.r(t),n.d(t,{createSwipeBackGesture:()=>s});var r=n(6587),c=n(545),o=n(6515);
/*!
 * (C) Ionic http://ionicframework.com - MIT License
 */
const s=(e,t,n,s,a)=>{const i=e.ownerDocument.defaultView,l=(0,c.i)(e),u=e=>{const t=50,{startX:n}=e;return l?n>=i.innerWidth-t:n<=t},h=e=>l?-e.deltaX:e.deltaX,d=e=>l?-e.velocityX:e.velocityX,k=e=>u(e)&&t(),w=e=>{const t=h(e),n=t/i.innerWidth;s(n)},p=e=>{const t=h(e),n=i.innerWidth,c=t/n,o=d(e),s=n/2,l=o>=0&&(o>.2||t>s),u=l?1-c:c,k=u*n;let w=0;if(k>5){const e=k/Math.abs(o);w=Math.min(e,540)}a(l,c<=0?.01:(0,r.h)(0,c,.9999),w)};return(0,o.createGesture)({el:e,gestureName:"goback-swipe",gesturePriority:40,threshold:10,canStart:k,onStart:n,onMove:w,onEnd:p})}}}]);
//# sourceMappingURL=521-legacy.4db510f0.js.map