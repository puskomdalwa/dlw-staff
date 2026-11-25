import{_ as g}from"./12-DYPrdulL.js";import{f as x}from"./3-BNysva2A.js";import{f as h}from"./15-BpjtG8xY.js";import{_ as u,o as f,a2 as _,c as i,e as s,f as l,g as t,w as e,F as c,s as n,t as p,C as m,j as y}from"./index-DNX_QOTQ.js";import{_ as T}from"./pageheader-F8tiMIeW.js";import{S as r}from"./showcodeCard-CDopq2Aa.js";let w={script:`
     <div class="btn-list">
                <button type="button" class="btn btn-primary btn-wave" data-bs-toggle="tooltip" :data-bs-placement="idx.text" :title="Tooltip on {idx.text}" v-for="(idx, index) in tooltipData.Tooltipdirtooltip" :key="index" >
                    Tooltip on {{idx.text}}
                </button>
            </div>`,data:`
         Tooltipdirtooltip = [
        { id: 1, text: "top" },
        { id: 2, text: "right" },
        { id: 3, text: "bottom" },
        { id: 4, text: "left" }
    ]`},z={script:`<div class="btn-list">
                <button type="button" :class="btn btn-{idx.color} btn-wave" data-bs-toggle="tooltip" :data-bs-custom-class="tooltip-{idx.color}" :data-bs-placement="idx.dir" :title="{idx.text} Tooltip" v-for="(idx, index) in tooltipData.Coloredtooltip" :key="index">
                    {{idx.text}} Tooltip
                </button>
            </div>`,data:`
        Coloredtooltip = [
        { id: 1, dir: "top", color: "primary", text: "Primary" },
        { id: 2, dir: "right", color: "secondary", text: "Secondary" },
        { id: 3, dir: "bottom", color: "warning", text: "Warning" },
        { id: 4, dir: "left", color: "info", text: "Info" },
        { id: 5, dir: "top", color: "success", text: "Success" },
        { id: 6, dir: "bottom", color: "danger", text: "Danger" },
        { id: 7, dir: "bottom", color: "light", text: "Light" },
        { id: 8, dir: "bottom", color: "dark", text: "Dark" }
    ]
        `},M={script:`
     <p class="text-muted mb-0">
                Hover on the link to view the <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-custom-class="tooltip-primary" title="Link Tooltip" class="text-primary">Tooltip</a>
            </p>`},C={script:`
 <a href="javascript:void(0);" data-bs-toggle="tooltip" :title="idx.text" :data-bs-custom-class="tooltip-{idx.color}" :class="me-3 svg-{idx.color}" v-for="(idx, index) in tooltipData.SVGtooltip" :key="index">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000">
                    <path d="M0 0h24v24H0V0z" fill="none" />
                    <path :d="idx.class" /></svg>
            </a>`,data:`
        SVGtooltip = [
        { id: 1, class: "M12 5.69l5 4.5V18h-2v-6H9v6H7v-7.81l5-4.5M12 3L2 12h3v8h6v-6h2v6h6v-8h3L12 3z", color: "primary", text: "Home" },
        { id: 2, class: "M22 6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6zm-2 0l-8 5-8-5h16zm0 12H4V8l8 5 8-5v10z", color: "secondary", text: "Message" },
        { id: 3, class: "M20,9V6h-2v3h-3v2h3v3h2v-3h3V9H20z M9,12c2.21,0,4-1.79,4-4c0-2.21-1.79-4-4-4S5,5.79,5,8C5,10.21,6.79,12,9,12z M9,6 c1.1,0,2,0.9,2,2c0,1.1-0.9,2-2,2S7,9.1,7,8C7,6.9,7.9,6,9,6z M15.39,14.56C13.71,13.7,11.53,13,9,13c-2.53,0-4.71,0.7-6.39,1.56  C1.61,15.07,1,16.1,1,17.22V20h16v-2.78C17,16.1,16.39,15.07,15.39,14.56z M15,18H3v-0.78c0-0.38,0.2-0.72,0.52-0.88 C4.71,15.73,6.63,15,9,15c2.37, 0,4.29,0.73,5.48,1.34C14.8,16.5,15,16.84,15,17.22V18z", color: "warning", text: "Add User" },
        { id: 4, class: "M4.01 6.03l7.51 3.22-7.52-1 .01-2.22m7.5 8.72L4 17.97v-2.22l7.51-1M2.01 3L2 10l15 2-15 2 .01 7L23 12 2.01 3z", color: "info", text: "Send File" },
        { id: 5, class: "M6 10c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z", color: "success", text: "Action" },
    ]
        `},V={script:`
<span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" title="Disabled tooltip">
                    <button class="btn btn-primary" type="button" disabled="">Disabled
                        button
                    </button>
                </span>`},k={script:`
<a href="javascript:void(0);" data-bs-toggle="tooltip" title="Alex Carey" data-bs-custom-class="tooltip-primary" class="avatar avatar-md me-2 online avatar-rounded">
                <img src="/images/faces/12.jpg" alt="img">
            </a>
            <a href="javascript:void(0);" data-bs-toggle="tooltip" title="Marina Kai" data-bs-custom-class="tooltip-primary" class="avatar avatar-lg me-2 online avatar-rounded">
                <img src="/images/faces/3.jpg" alt="img">
            </a>
            <a href="javascript:void(0);" data-bs-toggle="tooltip" title="Tim Cook" data-bs-custom-class="tooltip-primary" class="avatar avatar-xl me-2 offline avatar-rounded">
                <img src="/images/faces/15.jpg" alt="img">
            </a>`},S=[{id:1,text:"top"},{id:2,text:"right"},{id:3,text:"bottom"},{id:4,text:"left"}],H=[{id:1,dir:"top",color:"primary",text:"Primary"},{id:2,dir:"right",color:"secondary",text:"Secondary"},{id:3,dir:"bottom",color:"warning",text:"Warning"},{id:4,dir:"left",color:"info",text:"Info"},{id:5,dir:"top",color:"success",text:"Success"},{id:6,dir:"bottom",color:"danger",text:"Danger"},{id:7,dir:"bottom",color:"light",text:"Light"},{id:8,dir:"bottom",color:"dark",text:"Dark"}],L=[{id:1,class:"M12 5.69l5 4.5V18h-2v-6H9v6H7v-7.81l5-4.5M12 3L2 12h3v8h6v-6h2v6h6v-8h3L12 3z",color:"primary",text:"Home"},{id:2,class:"M22 6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6zm-2 0l-8 5-8-5h16zm0 12H4V8l8 5 8-5v10z",color:"secondary",text:"Message"},{id:3,class:"M20,9V6h-2v3h-3v2h3v3h2v-3h3V9H20z M9,12c2.21,0,4-1.79,4-4c0-2.21-1.79-4-4-4S5,5.79,5,8C5,10.21,6.79,12,9,12z M9,6 c1.1,0,2,0.9,2,2c0,1.1-0.9,2-2,2S7,9.1,7,8C7,6.9,7.9,6,9,6z M15.39,14.56C13.71,13.7,11.53,13,9,13c-2.53,0-4.71,0.7-6.39,1.56  C1.61,15.07,1,16.1,1,17.22V20h16v-2.78C17,16.1,16.39,15.07,15.39,14.56z M15,18H3v-0.78c0-0.38,0.2-0.72,0.52-0.88 C4.71,15.73,6.63,15,9,15c2.37, 0,4.29,0.73,5.48,1.34C14.8,16.5,15,16.84,15,17.22V18z",color:"warning",text:"Add User"},{id:4,class:"M4.01 6.03l7.51 3.22-7.52-1 .01-2.22m7.5 8.72L4 17.97v-2.22l7.51-1M2.01 3L2 10l15 2-15 2 .01 7L23 12 2.01 3z",color:"info",text:"Send File"},{id:5,class:"M6 10c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z",color:"success",text:"Action"}];const D={class:"row"},j={class:"col-xl-12"},A={class:"btn-list"},$=["data-bs-placement","title"],F={class:"row"},B={class:"col-xl-12"},G={class:"btn-list"},I=["data-bs-custom-class","data-bs-placement","title"],W={class:"row"},E={class:"col-xl-6"},N={class:"col-xl-6"},P=["title","data-bs-custom-class"],U={xmlns:"http://www.w3.org/2000/svg",height:"24px",viewBox:"0 0 24 24",width:"24px",fill:"#000000"},K=["d"],q={class:"col-xl-6"},O={class:"col-xl-6"},J={__name:"tooltips",setup(Q){const v={title:"Ui Elements",currentpage:"Tooltips",activepage:"Tooltips"};return f(()=>{[...document.querySelectorAll('[data-bs-toggle="tooltip"]')].map(a=>new _(a))}),(b,a)=>(s(),i(c,null,[l(T,{propData:v}),t("div",D,[t("div",j,[l(r,{title:"Tooltip Directions",code:w},{default:e(()=>[t("div",A,[(s(!0),i(c,null,n(S,(o,d)=>(s(),i("button",{type:"button",class:"btn btn-primary btn-wave","data-bs-toggle":"tooltip","data-bs-placement":o.text,title:`Tooltip on ${o.text}`,key:d}," Tooltip on "+p(o.text),9,$))),128))])]),_:1},8,["code"])])]),t("div",F,[t("div",B,[l(r,{title:"Colored Tooltips",code:z},{default:e(()=>[t("div",G,[(s(!0),i(c,null,n(H,(o,d)=>(s(),i("button",{type:"button",class:m(`btn btn-${o.color} btn-wave`),"data-bs-toggle":"tooltip","data-bs-custom-class":`tooltip-${o.color}`,"data-bs-placement":o.dir,title:`${o.text} Tooltip`,key:d},p(o.text)+" Tooltip ",11,I))),128))])]),_:1},8,["code"])])]),t("div",W,[t("div",E,[l(r,{title:"Tooltips on links",code:M},{default:e(()=>a[0]||(a[0]=[t("p",{class:"text-muted mb-0"},[y(" Hover on the link to view the "),t("a",{href:"javascript:void(0);","data-bs-toggle":"tooltip","data-bs-custom-class":"tooltip-primary",title:"Link Tooltip",class:"text-primary"},"Tooltip")],-1)])),_:1,__:[0]},8,["code"])]),t("div",N,[l(r,{title:"With an SVG's",code:C},{default:e(()=>[(s(!0),i(c,null,n(L,(o,d)=>(s(),i("a",{href:"javascript:void(0);","data-bs-toggle":"tooltip",title:o.text,"data-bs-custom-class":`tooltip-${o.color}`,class:m(`me-3 svg-${o.color}`),key:d},[(s(),i("svg",U,[a[1]||(a[1]=t("path",{d:"M0 0h24v24H0V0z",fill:"none"},null,-1)),t("path",{d:o.class},null,8,K)]))],10,P))),128))]),_:1},8,["code"])]),t("div",q,[l(r,{title:"Disabled elements",code:V},{default:e(()=>a[2]||(a[2]=[t("span",{class:"d-inline-block",tabindex:"0","data-bs-toggle":"tooltip",title:"Disabled tooltip"},[t("button",{class:"btn btn-primary",type:"button",disabled:""},"Disabled button ")],-1)])),_:1,__:[2]},8,["code"])]),t("div",O,[l(r,{title:"Tooltip For Images",code:k},{default:e(()=>a[3]||(a[3]=[t("a",{href:"javascript:void(0);","data-bs-toggle":"tooltip",title:"Alex Carey","data-bs-custom-class":"tooltip-primary",class:"avatar avatar-md me-2 online avatar-rounded"},[t("img",{src:g,alt:"img"})],-1),t("a",{href:"javascript:void(0);","data-bs-toggle":"tooltip",title:"Marina Kai","data-bs-custom-class":"tooltip-primary",class:"avatar avatar-lg me-2 online avatar-rounded"},[t("img",{src:x,alt:"img"})],-1),t("a",{href:"javascript:void(0);","data-bs-toggle":"tooltip",title:"Tim Cook","data-bs-custom-class":"tooltip-primary",class:"avatar avatar-xl me-2 offline avatar-rounded"},[t("img",{src:h,alt:"img"})],-1)])),_:1,__:[3]},8,["code"])])])],64))}},at=u(J,[["__scopeId","data-v-f240742e"]]);export{at as default};
