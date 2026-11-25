import{v as m,p as n,a as d}from"./filepond-plugin-image-preview.esm-D0myrkI7.js";/* empty css                                          */import{_ as c}from"./pageheader-F8tiMIeW.js";import{S as a}from"./showcodeCard-CDopq2Aa.js";import{d as u,b,c as F,e as _,f as e,g as l,w as i,K as s,F as g,_ as v}from"./index-DNX_QOTQ.js";let x={script:`
<div class="mb-3">
                <label for="formFile" class="form-label">Default file input example</label>
                <input class="form-control" type="file" id="formFile">
            </div>
            <div class="mb-3">
                <label for="formFileMultiple" class="form-label">Multiple files input
                    example</label>
                <input class="form-control" type="file" id="formFileMultiple" multiple>
            </div>
            <div class="mb-3">
                <label for="formFileDisabled" class="form-label">Disabled file input
                    example</label>
                <input class="form-control" type="file" id="formFileDisabled" disabled>
            </div>
            <div class="mb-3">
                <label for="formFileSm" class="form-label">Small file input example</label>
                <input class="form-control form-control-sm" id="formFileSm" type="file">
            </div>
            <div>
                <label for="formFileLg" class="form-label">Large file input example</label>
                <input class="form-control form-control-lg" id="formFileLg" type="file">
            </div>`},y={script:`
    <file-pond name="test" ref="pond" label-idle="Drop files here or <span class='filepond--label-action'>Browse</span>" allow-multiple="true" max-files="3" accepted-file-types="image/jpeg, image/png" v-bind:files="myFiles" />`},D={script:`
     <file-pond name="test" ref="pond" label-idle="Drop files here or <span class='filepond--label-action'>Browse</span>" allow-multiple="false" max-files="1" accepted-file-types="image/jpeg, image/png" v-bind:files="myFiles" v-bind:data-style-panel-layout="'circle'" />`},w={script:' <DropZone :maxFiles="4" :uploadOnDrop="true" :multipleUpload="true" :parallelUpload="3" />'};const h={class:"row"},U={class:"col-xl-12"},S={class:"col-xl-12"},B={class:"row"},M={class:"col-xl-12"},L={class:"col-xl-12"},j={class:"row"},C={class:"col-xl-12"},I=u({__name:"file-uploads",setup(P){const t=m(d,n),r={title:"Forms",subtitle:"Form Elements",currentpage:"File Uploads",activepage:"File Uploads"};var p=[];return(Z,o)=>{const f=b("DropZone");return _(),F(g,null,[e(c,{propData:r}),l("div",h,[l("div",U,[e(a,{code:x,title:"Bootstrap File Input"},{default:i(()=>o[0]||(o[0]=[l("div",{class:"mb-3"},[l("label",{for:"formFile",class:"form-label"},"Default file input example"),l("input",{class:"form-control py-1",type:"file",id:"formFile"})],-1),l("div",{class:"mb-3"},[l("label",{for:"formFileMultiple",class:"form-label"},"Multiple files input example"),l("input",{class:"form-control py-1",type:"file",id:"formFileMultiple",multiple:""})],-1),l("div",{class:"mb-3"},[l("label",{for:"formFileDisabled",class:"form-label"},"Disabled file input example"),l("input",{class:"form-control py-1",type:"file",id:"formFileDisabled",disabled:""})],-1),l("div",{class:"mb-3"},[l("label",{for:"formFileSm",class:"form-label"},"Small file input example"),l("input",{class:"form-control py-1 form-control-sm ps-2",id:"formFileSm",type:"file"})],-1),l("div",null,[l("label",{for:"formFileLg",class:"form-label"},"Large file input example"),l("input",{class:"form-control form-control-lg ps-2",id:"formFileLg",type:"file"})],-1)])),_:1,__:[0]},8,["code"])]),l("div",S,[o[1]||(o[1]=l("h6",{class:"mb-3"},"Filepond:",-1)),l("div",B,[l("div",M,[e(a,{code:y,title:"Multiple Upload"},{default:i(()=>[e(s(t),{name:"test",ref:"pond","label-idle":"Drop files here or <span class='filepond--label-action'>Browse</span>","allow-multiple":"true","max-files":"3","accepted-file-types":"image/jpeg, image/png",files:s(p)},null,8,["files"])]),_:1},8,["code"])]),l("div",L,[e(a,{code:D,title:"Single Upload"},{default:i(()=>[e(s(t),{name:"test",ref:"pond","label-idle":"Drop files here or <span class='filepond--label-action'>Browse</span>","allow-multiple":"false","max-files":"1","accepted-file-types":"image/jpeg, image/png",files:s(p),"data-style-panel-layout":"compact circle"},null,8,["files"])]),_:1},8,["code"])])])])]),l("div",j,[l("div",C,[e(a,{code:w,title:"Dropzone",class:"custom-upload"},{default:i(()=>[e(f,{maxFiles:4,uploadOnDrop:!0,multipleUpload:!0,parallelUpload:3})]),_:1},8,["code"])])])],64)}}}),V=v(I,[["__scopeId","data-v-3f7d337e"]]);export{V as default};
