import{_ as y,o as _,a2 as m,ak as h,l as w,c as e,e as a,f as l,g as o,w as r,F as i,s as p,t as b,C as v}from"./index-DITeVAQq.js";import{_ as P}from"./pageheader-CdNBw8TR.js";import{S as c}from"./showcodeCard-BwifnrSF.js";let D={script:`
    <div class="btn-list">
                <a tabindex="0" class="btn btn-outline-primary btn-wave" role="button" data-bs-toggle="popover" :data-bs-placement="idx.class" :title="Popover {idx.text} " data-bs-content="And here's some amazing content. It's very engaging. Right?" v-for="(idx, index) in popoverData.Defaultalerts" :key="index">Popover
                    {{idx.text}}
                </a>
            </div>`,data:`
         Defaultalerts = [
        { id: 1, text: "Top", class: "top" },
        { id: 2, text: "Right", class: "auto" },
        { id: 3, text: "Bottom", class: "bottom" },
        { id: 4, text: "Left", class: "left" },
    ]`},z={script:`
    <div class="btn-list">
                <button type="button" :class="btn btn-{idx.color} btn-wave" data-bs-toggle="popover" :data-bs-placement="idx.class" :data-bs-custom-class="header-{idx.color1}" title="Color Header" :data-bs-content="Popover with {idx.color1} header." v-for="(idx, index) in popoverData.Colorheaderalerts" :key="index">
                    Header {{idx.text}}
                </button>
            </div>`,data:`
        Colorheaderalerts = [
        { id: 1, text: "Primary", class: "top", color: "outline-primary", color1: "primary", color2: "" },
        { id: 2, text: "Secondary", class: "right", color: "outline-secondary", color1: "secondary", color2: "" },
        { id: 3, text: "Info", class: "bottom", color: "outline-info", color1: "info", color2: "bs-popover-auto" },
        { id: 4, text: "Warning", class: "left", color: "outline-warning", color1: "warning", color2: "" },
        { id: 5, text: "Success", class: "top", color: "outline-success", color1: "success", color2: "" },
        { id: 6, text: "Danger", class: "top", color: "outline-danger", color1: "danger", color2: "" },
    ]
        `},C={script:`
     <div class="btn-list">
                <button type="button" :class="btn btn-{idx.color1} btn-wave" data-bs-toggle="popover" :data-bs-placement="idx.class" :data-bs-custom-class="popover-{idx.color1}" title="Color Background" :data-bs-content="Popover with {idx.text} background." v-for="(idx, index) in popoverData.Colredalerts" :key="index">
                    {{idx.text}}
                </button>
            </div>`,data:`
        Colredalerts = [
        { id: 1, text: "Primary", class: "top", color1: "primary" },
        { id: 2, text: "Secondary", class: "right", color1: "secondary" },
        { id: 3, text: "Info", class: "bottom", color1: "info" },
        { id: 4, text: "Warning", class: "left", color1: "warning" },
        { id: 5, text: "Success", class: "top", color1: "success" },
        { id: 6, text: "Danger", class: "right", color1: "danger" },
        { id: 7, text: "Teal", class: "bottom", color1: "teal" },
        { id: 8, text: "Purple", class: "left", color1: "purple" },
    ]
        `},k={script:`
     <div class="btn-list">
                <button type="button" :class="btn btn-{idx.color1}-light btn-wave" data-bs-toggle="popover" :data-bs-placement="idx.class" :data-bs-custom-class="popover-{idx.color1}-light" title="Light Background" :data-bs-content="Popover with light {idx.text} background." v-for="(idx, index) in popoverData.Colredalerts" :key="index">
                    {{idx.text}}
                </button>
            </div>`,data:`
        Colredalerts = [
        { id: 1, text: "Primary", class: "top", color1: "primary" },
        { id: 2, text: "Secondary", class: "right", color1: "secondary" },
        { id: 3, text: "Info", class: "bottom", color1: "info" },
        { id: 4, text: "Warning", class: "left", color1: "warning" },
        { id: 5, text: "Success", class: "top", color1: "success" },
        { id: 6, text: "Danger", class: "right", color1: "danger" },
        { id: 7, text: "Teal", class: "bottom", color1: "teal" },
        { id: 8, text: "Purple", class: "left", color1: "purple" },
    ]
        `},S={script:`
     <span class="d-inline-block" tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Disabled popover">
                <button class="btn btn-primary" type="button" disabled>Disabled
                    button</button>
            </span>`},B={script:`
   <a class="me-4 svg-primary" href="javascript:void(0)" data-bs-toggle="popover" data-bs-placement="top" data-bs-custom-class="popover-primary only-body" data-bs-content="This popover is used to provide details about this icon.">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000">
                        <path d="M0 0h24v24H0V0z" fill="none" />
                        <path d="M11 18h2v-2h-2v2zm1-16C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-2.21 0-4 1.79-4 4h2c0-1.1.9-2 2-2s2 .9 2 2c0 2-3 1.75-3 5h2c0-2.25 3-2.5 3-5 0-2.21-1.79-4-4-4z" /></svg>
                </a>
                <a class="me-4 svg-secondary" href="javascript:void(0)" data-bs-toggle="popover" data-bs-placement="left" data-bs-custom-class="popover-secondary only-body" data-bs-content="This popover is used to provide information about this icon.">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000">
                        <path d="M0 0h24v24H0V0z" fill="none" />
                        <path d="M11 7h2v2h-2zm0 4h2v6h-2zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" /></svg>
                </a>`},I={script:` <a tabindex="0" :class="btn btn-{idx.color} m-1" role="button" data-bs-toggle="popover" data-bs-trigger="focus" :data-bs-placement="idx.class" title="Dismissible popover" :data-bs-content="And here's some amazing content. It's very engaging. {idx.class}?"v-for="(idx, index) in popoverData.Dismissiblealerts" :key="index" >Popover Dismiss
            </a>`,data:`
            Dismissiblealerts = [
        { id: 1, color: "primary", class: "top" },
        { id: 2, color: "secondary", class: "right" },
        { id: 3, color: "info", class: "left" },
        { id: 4, color: "warning", class: "bottom" },
    ]`},T=[{id:1,text:"Top",class:"top"},{id:2,text:"Right",class:"auto"},{id:3,text:"Bottom",class:"bottom"},{id:4,text:"Left",class:"left"}],$=[{id:1,text:"Primary",class:"top",color:"outline-primary",color1:"primary",color2:""},{id:2,text:"Secondary",class:"right",color:"outline-secondary",color1:"secondary",color2:""},{id:3,text:"Info",class:"bottom",color:"outline-info",color1:"info",color2:"bs-popover-auto"},{id:4,text:"Warning",class:"left",color:"outline-warning",color1:"warning",color2:""},{id:5,text:"Success",class:"top",color:"outline-success",color1:"success",color2:""},{id:6,text:"Danger",class:"top",color:"outline-danger",color1:"danger",color2:""}],x=[{id:1,text:"Primary",class:"top",color1:"primary"},{id:2,text:"Secondary",class:"right",color1:"secondary"},{id:3,text:"Info",class:"bottom",color1:"info"},{id:4,text:"Warning",class:"left",color1:"warning"},{id:5,text:"Success",class:"top",color1:"success"},{id:6,text:"Danger",class:"right",color1:"danger"},{id:7,text:"Teal",class:"bottom",color1:"teal"},{id:8,text:"Purple",class:"left",color1:"purple"}],H=[{id:1,color:"primary",class:"top"},{id:2,color:"secondary",class:"right"},{id:3,color:"info",class:"left"},{id:4,color:"warning",class:"bottom"}];const L={class:"row"},M={class:"col-xl-12"},A={class:"btn-list"},E=["data-bs-placement","title"],V={class:"col-xl-12"},j={class:"btn-list"},W=["data-bs-placement","data-bs-custom-class","data-bs-content"],R={class:"row"},q={class:"col-xl-12"},F={class:"btn-list"},N=["data-bs-placement","data-bs-custom-class","data-bs-content"],U={class:"row"},G={class:"col-xl-12"},J={class:"btn-list"},K=["data-bs-placement","data-bs-custom-class","data-bs-content"],O={class:"row"},Q={class:"col-xl-12"},X=["data-bs-placement","data-bs-content"],Y={class:"col-xl-6"},Z={class:"col-xl-6"},tt={__name:"popovers",setup(ot){let g=[],u=[];_(()=>{document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(t=>{if(!m.getInstance(t)){const s=new m(t);g.push(s)}}),document.querySelectorAll('[data-bs-toggle="popover"]').forEach(t=>{if(!h.getInstance(t)){const s=new h(t);u.push(s)}})}),w(()=>{g.forEach(n=>n.dispose()),g=[],u.forEach(n=>n.dispose()),u=[]});const f={title:"Ui Elements",currentpage:"Popovers",activepage:"Popovers"};return(n,d)=>(a(),e(i,null,[l(P,{propData:f}),o("div",L,[o("div",M,[l(c,{title:"Default Popovers",code:D},{default:r(()=>[o("div",A,[(a(!0),e(i,null,p(T,(t,s)=>(a(),e("a",{tabindex:"0",class:"btn btn-outline-primary btn-wave",role:"button","data-bs-toggle":"popover","data-bs-placement":t.class,title:`Popover ${t.text} `,"data-bs-content":"And here's some amazing content. It's very engaging. Right?",key:s},"Popover "+b(t.text),9,E))),128))])]),_:1},8,["code"])]),o("div",V,[l(c,{title:"Colored Headers",code:z},{default:r(()=>[o("div",j,[(a(!0),e(i,null,p($,(t,s)=>(a(),e("button",{type:"button",class:v(`btn btn-${t.color} btn-wave`),"data-bs-toggle":"popover","data-bs-placement":t.class,"data-bs-custom-class":`header-${t.color1}`,title:"Color Header","data-bs-content":`Popover with ${t.color1} header.`,key:s}," Header "+b(t.text),11,W))),128))])]),_:1},8,["code"])])]),o("div",R,[o("div",q,[l(c,{title:"Colored Popovers",code:C},{default:r(()=>[o("div",F,[(a(!0),e(i,null,p(x,(t,s)=>(a(),e("button",{type:"button",class:v(`btn btn-${t.color1} btn-wave`),"data-bs-toggle":"popover","data-bs-placement":t.class,"data-bs-custom-class":`popover-${t.color1}`,title:"Color Background","data-bs-content":`Popover with ${t.text} background.`,key:s},b(t.text),11,N))),128))])]),_:1},8,["code"])])]),o("div",U,[o("div",G,[l(c,{title:"Light Popovers",code:k},{default:r(()=>[o("div",J,[(a(!0),e(i,null,p(x,(t,s)=>(a(),e("button",{type:"button",class:v(`btn btn-${t.color1}-light btn-wave`),"data-bs-toggle":"popover","data-bs-placement":t.class,"data-bs-custom-class":`popover-${t.color1}-light`,title:"Light Background","data-bs-content":`Popover with light ${t.text} background.`,key:s},b(t.text),11,K))),128))])]),_:1},8,["code"])])]),o("div",O,[o("div",Q,[l(c,{title:"Dismissible Popovers",code:I,customCardBodyClass:"d-flex flex-wrap justify-content-between"},{default:r(()=>[(a(!0),e(i,null,p(H,(t,s)=>(a(),e("a",{tabindex:"0",class:v(`btn btn-${t.color} m-1`),role:"button","data-bs-toggle":"popover","data-bs-trigger":"focus","data-bs-placement":t.class,title:"Dismissible popover","data-bs-content":`And here's some amazing content. It's very engaging. ${t.class}?`,key:s},"Popover Dismiss ",10,X))),128))]),_:1},8,["code"])]),o("div",Y,[l(c,{title:"Disabled Popover",code:S},{default:r(()=>d[0]||(d[0]=[o("span",{class:"d-inline-block",tabindex:"0","data-bs-toggle":"popover","data-bs-trigger":"hover focus","data-bs-content":"Disabled popover"},[o("button",{class:"btn btn-primary",type:"button",disabled:""},"Disabled button")],-1)])),_:1,__:[0]},8,["code"])]),o("div",Z,[l(c,{title:"Icon Popovers",code:B},{default:r(()=>d[1]||(d[1]=[o("a",{class:"me-4 svg-primary",href:"javascript:void(0)","data-bs-toggle":"popover","data-bs-placement":"top","data-bs-custom-class":"popover-primary only-body","data-bs-content":"This popover is used to provide details about this icon."},[o("svg",{xmlns:"http://www.w3.org/2000/svg",height:"24px",viewBox:"0 0 24 24",width:"24px",fill:"#000000"},[o("path",{d:"M0 0h24v24H0V0z",fill:"none"}),o("path",{d:"M11 18h2v-2h-2v2zm1-16C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-2.21 0-4 1.79-4 4h2c0-1.1.9-2 2-2s2 .9 2 2c0 2-3 1.75-3 5h2c0-2.25 3-2.5 3-5 0-2.21-1.79-4-4-4z"})])],-1),o("a",{class:"me-4 svg-secondary",href:"javascript:void(0)","data-bs-toggle":"popover","data-bs-placement":"left","data-bs-custom-class":"popover-secondary only-body","data-bs-content":"This popover is used to provide information about this icon."},[o("svg",{xmlns:"http://www.w3.org/2000/svg",height:"24px",viewBox:"0 0 24 24",width:"24px",fill:"#000000"},[o("path",{d:"M0 0h24v24H0V0z",fill:"none"}),o("path",{d:"M11 7h2v2h-2zm0 4h2v6h-2zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"})])],-1)])),_:1,__:[1]},8,["code"])])])],64))}},lt=y(tt,[["__scopeId","data-v-887c7c5a"]]);export{lt as default};
