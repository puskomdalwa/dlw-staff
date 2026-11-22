import{_ as $}from"./1-D00sKJHD.js";import{f as m}from"./2-DrU-BhZA.js";import{f as g}from"./3-BNysva2A.js";import{f as v}from"./4-CE-4OtDa.js";import{f as k}from"./5-B61nG-q6.js";import{f as y}from"./6-B4-aqYQy.js";import{f as b}from"./7-BS-VUzae.js";import{f as u}from"./8-Ckom4t80.js";import{f as x}from"./9-B8wHo7KF.js";import{f as w}from"./10-_wOsvybG.js";import{_ as D}from"./12-DYPrdulL.js";import{f as p}from"./13-DjOIqxVg.js";import{f as _}from"./14-K6D5l3vM.js";import{f as j}from"./15-BpjtG8xY.js";import{_ as W}from"./pageheader-DS_TsNzx.js";import{S as d}from"./showcodeCard-CNqB0ABy.js";import{_ as I,c,e as i,f as r,g as s,w as o,F as e,s as n,C as l,t as A}from"./index-Dv1CqIWO.js";let X={script:`
    <span :class="avatar me-2 {idx.class}" v-for="(idx, index) in avatarData.Avatardata" :key="index">
                <img :src="idx.src" alt="img">
            </span>`,data:`
        Avatardata = [
        { id: 1, class: "avatar-radius-0", src: "/images/faces/1.jpg" },
        { id: 1, class: "", src: "/images/faces/2.jpg" },
        { id: 1, class: "avatar-rounded", src: "/images/faces/3.jpg" },
    ]`},z={script:`
    <span :class="avatar avatar-{idx.class} me-2" v-for="(idx, index) in avatarData.Avatarsize" :key="index">
                <img :src="idx.src" alt="img">
            </span>`,data:`
         Avatarsize = [
        { id: 1, class: "xs", src: "/images/faces/4.jpg" },
        { id: 2, class: "sm", src: "/images/faces/5.jpg" },
        { id: 3, class: "md", src: "/images/faces/6.jpg" },
        { id: 4, class: "lg", src: "/images/faces/7.jpg" },
        { id: 5, class: "xl", src: "/images/faces/8.jpg" },
        { id: 6, class: "xxl", src: "/images/faces/9.jpg" },
    ]
        `},L={script:`
    <span :class="avatar avatar-{idx.class} me-2 avatar-rounded" v-for="(idx, index) in avatarData.Avataricon" :key="index">
                <img :src="idx.src" alt="img">
                <a href="javascript:void(0);" :class="badge bg-{idx.class1} rounded-pill avatar-badge"><i :class="fe fe-{idx.icon}"></i></a>
            </span>`,data:`
         Avataricon = [
        { id: 1, class: "xs", src: "/images/faces/2.jpg", icon: "camera", class1: "success" },
        { id: 2, class: "sm", src: "/images/faces/3.jpg", icon: "edit", class1: "secondary" },
        { id: 3, class: "md", src: "/images/faces/14.jpg", icon: "plus", class1: "warning" },
        { id: 4, class: "lg", src: "/images/faces/13.jpg", icon: "edit", class1: "info" },
        { id: 5, class: "xl", src: "/images/faces/15.jpg", icon: "camera", class1: "success" },
        { id: 6, class: "xxl", src: "/images/faces/9.jpg", icon: "plus", class1: "danger" },
    ]
        `},B={script:`
   <span :class="avatar avatar-{idx.class} me-2 online avatar-rounded" v-for="(idx, index) in avatarData.Avataronline" :key="index">
                <img :src="idx.src" alt="img">
            </span>`,data:`
        Avataronline = [
        { id: 1, class: "xs online", src: "/images/faces/8.jpg" },
        { id: 2, class: "sm online", src: "/images/faces/10.jpg" },
        { id: 3, class: "md online", src: "/images/faces/12.jpg" },
        { id: 4, class: "lg online", src: "/images/faces/13.jpg" },
        { id: 5, class: "xl online", src: "/images/faces/14.jpg" },
        { id: 6, class: "xxl online", src: "/images/faces/15.jpg" },
    ]
        `},C={script:`
    <span :class="avatar avatar-{idx.class} me-2 offline avatar-rounded" v-for="(idx, index) in avatarData.Avataroffline" :key="index">
                <img :src="idx.src" alt="img">
            </span>`,data:`
        Avataroffline = [
        { id: 1, class: "xs offline", src: "/images/faces/2.jpg" },
        { id: 2, class: "sm offline", src: "/images/faces/3.jpg" },
        { id: 3, class: "md offline", src: "/images/faces/4.jpg" },
        { id: 4, class: "lg offline", src: "/images/faces/5.jpg" },
        { id: 5, class: "xl offline", src: "/images/faces/6.jpg" },
        { id: 6, class: "xxl offline", src: "/images/faces/7.jpg" },
    ]
        `},G={script:`
     <span :class="avatar avatar-{idx.class} me-2 avatar-rounded" v-for="(idx, index) in avatarData.Avatarnumber" :key="index">
                <img :src="idx.src" alt="img">
                <span :class="badge rounded-pill bg-{idx.class1} avatar-badge">{{idx.data}}</span>
            </span>`,data:`
         Avatarnumber = [
        { id: 1, class: "xs", src: "/images/faces/2.jpg", icon: "camera", class1: "primary", data: '2' },
        { id: 2, class: "sm", src: "/images/faces/3.jpg", icon: "edit", class1: "secondary", data: '5' },
        { id: 3, class: "md", src: "/images/faces/14.jpg", icon: "plus", class1: "warning", data: '1' },
        { id: 4, class: "lg", src: "/images/faces/13.jpg", icon: "edit", class1: "info", data: '7' },
        { id: 5, class: "xl", src: "/images/faces/15.jpg", icon: "camera", class1: "success", data: '3' },
        { id: 6, class: "xxl", src: "/images/faces/9.jpg", icon: "plus", class1: "danger", data: '9' },
    ]
        `},M={script:`
    <span :class="avatar avatar-{idx.data} m-2 bg-{idx.color}" v-for="(idx, index) in avatarData.Avatarinitial" :key="index">
                {{idx.data1}}
            </span>`,data:`
         Avatarinitial = [
        { id: 1, data: "xs", color: "primary", data1: "XS" },
        { id: 2, data: "sm", color: "secondary", data1: "SM" },
        { id: 3, data: "md", color: "warning", data1: "MD" },
        { id: 4, data: "lg", color: "danger", data1: "LG" },
        { id: 5, data: "xl", color: "success", data1: "XL" },
        { id: 6, data: "xxl", color: "info", data1: "XXL" },
    ]
        `},N={script:`
     <span class="avatar" v-for="(idx, index) in avatarData.Avatarstack" :key="index">
                <img :src="idx.src" alt="img">
            </span>`,data:`
        Avatarstack = [
        { id: 1, src: "/images/faces/2.jpg" },
        { id: 2, src: "/images/faces/8.jpg" },
        { id: 3, src: "/images/faces/2.jpg" },
        { id: 4, src: "/images/faces/10.jpg" },
        { id: 5, src: "/images/faces/4.jpg" },
        { id: 6, src: "/images/faces/13.jpg" },
    ]
        `},O={script:`
     <span class="avatar avatar-rounded" v-for="(idx, index) in avatarData.Avatarstack" :key="index">
                <img :src="idx.src" alt="img">
            </span>`,data:`
        Avatarstack = [
        { id: 1, src: "/images/faces/2.jpg" },
        { id: 2, src: "/images/faces/8.jpg" },
        { id: 3, src: "/images/faces/2.jpg" },
        { id: 4, src: "/images/faces/10.jpg" },
        { id: 5, src: "/images/faces/4.jpg" },
        { id: 6, src: "/images/faces/13.jpg" },
    ]
        `},F=[{id:1,class:"avatar-radius-0",src:$},{id:1,class:"",src:m},{id:1,class:"avatar-rounded",src:g}],V=[{id:1,class:"xs",src:m,icon:"camera",class1:"success"},{id:2,class:"sm",src:g,icon:"edit",class1:"secondary"},{id:3,class:"md",src:_,icon:"plus",class1:"warning"},{id:4,class:"lg",src:p,icon:"edit",class1:"info"},{id:5,class:"xl",src:j,icon:"camera",class1:"success"},{id:6,class:"xxl",src:x,icon:"plus",class1:"danger"}],E=[{id:1,class:"xs",src:v},{id:2,class:"sm",src:k},{id:3,class:"md",src:y},{id:4,class:"lg",src:b},{id:5,class:"xl",src:u},{id:6,class:"xxl",src:x}],P=[{id:1,class:"xs online",src:u},{id:2,class:"sm online",src:w},{id:3,class:"md online",src:D},{id:4,class:"lg online",src:p},{id:5,class:"xl online",src:_},{id:6,class:"xxl online",src:j}],R=[{id:1,class:"xs offline",src:m},{id:2,class:"sm offline",src:g},{id:3,class:"md offline",src:v},{id:4,class:"lg offline",src:k},{id:5,class:"xl offline",src:y},{id:6,class:"xxl offline",src:b}],T=[{id:1,class:"xs",src:m,icon:"camera",class1:"primary",data:"2"},{id:2,class:"sm",src:g,icon:"edit",class1:"secondary",data:"5"},{id:3,class:"md",src:_,icon:"plus",class1:"warning",data:"1"},{id:4,class:"lg",src:p,icon:"edit",class1:"info",data:"7"},{id:5,class:"xl",src:j,icon:"camera",class1:"success",data:"3"},{id:6,class:"xxl",src:x,icon:"plus",class1:"danger",data:"9"}],h=[{id:1,src:m},{id:2,src:u},{id:3,src:m},{id:4,src:w},{id:5,src:v},{id:6,src:p}],U=[{id:1,data:"xs",color:"primary",data1:"XS"},{id:2,data:"sm",color:"secondary",data1:"SM"},{id:3,data:"md",color:"warning",data1:"MD"},{id:4,data:"lg",color:"danger",data1:"LG"},{id:5,data:"xl",color:"success",data1:"XL"},{id:6,data:"xxl",color:"info",data1:"XXL"}];const q={class:"row"},H={class:"col-xl-6 col-lg-6 col-md-12 col-sm-12"},J=["src"],K={class:"col-xl-6 col-lg-6 col-md-12 col-sm-12"},Q=["src"],Y={class:"col-xl-6 col-lg-6 col-md-12 col-sm-12"},Z=["src"],aa={class:"col-xl-6 col-lg-6 col-md-12 col-sm-12"},sa=["src"],ca={class:"col-xl-6 col-lg-6 col-md-12 col-sm-12"},ia=["src"],ta={class:"col-xl-6 col-lg-6 col-md-12 col-sm-12"},ra=["src"],ea={class:"row"},la={class:"col-xl-12 col-lg-6 col-md-12 col-sm-12"},da={class:"col-xl-12 col-lg-6 col-md-12 col-sm-12"},oa={class:"avatar-list-stacked"},na=["src"],ma={class:"col-xl-12 col-lg-6 col-md-12 col-sm-12"},fa={class:"avatar-list-stacked"},ga=["src"],pa={__name:"avatars",setup(va){const S={title:"Utilities",currentpage:"Avatars",activepage:"Avatars"};return(ua,f)=>(i(),c(e,null,[r(W,{propData:S}),s("div",q,[s("div",H,[r(d,{title:"Avatars",customCardBodyClass:"py-4",code:X},{default:o(()=>[(i(!0),c(e,null,n(F,(a,t)=>(i(),c("span",{class:l(`avatar me-2 ${a.class}`),key:t},[s("img",{src:a.src,alt:"img"},null,8,J)],2))),128))]),_:1},8,["code"])]),s("div",K,[r(d,{code:z,title:"Avatar Sizes<p class='subtitle text-muted fs-12 fw-normal'>Avatars of different sizes</p>"},{default:o(()=>[(i(!0),c(e,null,n(E,(a,t)=>(i(),c("span",{class:l(`avatar avatar-${a.class} me-2`),key:t},[s("img",{src:a.src,alt:"img"},null,8,Q)],2))),128))]),_:1},8,["code"])]),s("div",Y,[r(d,{code:L,title:"Avatar With Icons<p class='subtitle text-muted fs-12 fw-normal'>Avatar contains icons to perform respective action.</p>"},{default:o(()=>[(i(!0),c(e,null,n(V,(a,t)=>(i(),c("span",{class:l(`avatar avatar-${a.class} me-2 avatar-rounded`),key:t},[s("img",{src:a.src,alt:"img"},null,8,Z),s("a",{href:"javascript:void(0);",class:l(`badge bg-${a.class1} rounded-pill avatar-badge`)},[s("i",{class:l(`fe fe-${a.icon}`)},null,2)],2)],2))),128))]),_:1},8,["code"])]),s("div",aa,[r(d,{code:B,title:"Avatar With Online Status Indicators<p class='subtitle text-muted fs-12 fw-normal'>avatars having online status indicator.</p>"},{default:o(()=>[(i(!0),c(e,null,n(P,(a,t)=>(i(),c("span",{class:l(`avatar avatar-${a.class} me-2 online avatar-rounded`),key:t},[s("img",{src:a.src,alt:"img"},null,8,sa)],2))),128))]),_:1},8,["code"])]),s("div",ca,[r(d,{code:C,title:"Avatar With Offline Status Indicators<p class='subtitle text-muted fs-12 fw-normal'>avatars having a offline status indicator.</p>"},{default:o(()=>[(i(!0),c(e,null,n(R,(a,t)=>(i(),c("span",{class:l(`avatar avatar-${a.class} me-2 offline avatar-rounded`),key:t},[s("img",{src:a.src,alt:"img"},null,8,ia)],2))),128))]),_:1},8,["code"])]),s("div",ta,[r(d,{code:G,title:"Avatars With Number Badges<p class='subtitle text-muted fs-12 fw-normal'>Avatar numbers indicates the no. of unread notififactions/messages.</p>"},{default:o(()=>[(i(!0),c(e,null,n(T,(a,t)=>(i(),c("span",{class:l(`avatar avatar-${a.class} me-2 avatar-rounded`),key:t},[s("img",{src:a.src,alt:"img"},null,8,ra),s("span",{class:l(`badge rounded-pill bg-${a.class1} avatar-badge`)},A(a.data),3)],2))),128))]),_:1},8,["code"])])]),s("div",ea,[s("div",la,[r(d,{code:M,title:"Avatar With Initials<p class='subtitle text-muted fs-12 fw-normal'>Avatar contains intials when user profile doesn't exist.</p>"},{default:o(()=>[(i(!0),c(e,null,n(U,(a,t)=>(i(),c("span",{class:l(`avatar avatar-${a.data} m-2 bg-${a.color}`),key:t},A(a.data1),3))),128))]),_:1},8,["code"])]),s("div",da,[r(d,{code:N,title:"Stacked Avatars<p class='subtitle text-muted fs-12 fw-normal'>Group of avatars stacked together.</p>"},{default:o(()=>[s("div",oa,[(i(!0),c(e,null,n(h,(a,t)=>(i(),c("span",{class:"avatar",key:t},[s("img",{src:a.src,alt:"img"},null,8,na)]))),128)),f[0]||(f[0]=s("a",{class:"avatar bg-primary text-fixed-white",href:"javascript:void(0);"}," +8 ",-1))])]),_:1},8,["code"])]),s("div",ma,[r(d,{code:O,title:" Rounded Stacked Avatars<p class='subtitle text-muted fs-12 fw-normal'>Group of avatars stacked together.</p>"},{default:o(()=>[s("div",fa,[(i(!0),c(e,null,n(h,(a,t)=>(i(),c("span",{class:"avatar avatar-rounded",key:t},[s("img",{src:a.src,alt:"img"},null,8,ga)]))),128)),f[1]||(f[1]=s("a",{class:"avatar bg-primary avatar-rounded text-fixed-white",href:"javascript:void(0);"}," +8 ",-1))])]),_:1},8,["code"])])])],64))}},Ba=I(pa,[["__scopeId","data-v-1c356d5e"]]);export{Ba as default};
