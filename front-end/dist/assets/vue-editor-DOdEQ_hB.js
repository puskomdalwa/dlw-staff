import{_ as u}from"./pageheader-ARTq5r4u.js";import{S as l}from"./showcodeCard-B2h7mXsZ.js";import{_ as p,b as m,c as b,e as f,f as e,g as o,w as a,F as h}from"./index-DemBhOs_.js";let _={script:`
    <vue-editor v-model="content"></vue-editor>`},v={script:`

        data(){ 
            return {
                ...
            customToolbar: [
                ["bold", "italic", "underline"],
                [{ list: "ordered" }, { list: "bullet" }],
                ["image", "code-block"]
            ],
        }}
    <vue-editor v-model="content1" :editorToolbar="customToolbar"></vue-editor>`};const w={class:"row"},g={class:"col-xl-12"},x={class:"row"},T={class:"col-xl-12"},s='<h4><strong>vue3-editor</strong> is a free, open source <a href="https://www.npmjs.com/package/vue3-editor" rel="noopener noreferrer" target="_blank">vue3-editor</a> built for the modern web. With its modular architecture and expressive API, it is completely customizable to fit any need.</h4><p><br></p><ol><li>Write text select and edit with multiple options.</li><li>This is quill snow editor.</li><li>Easy to customize and flexible.</li></ol><p><br></p><h4>Vue3 officially supports a standard toolbar theme and a depends on quill.</h4><p><br></p>',d='<h4><strong>vue3-editor</strong> is a free, open source <a href="https://www.npmjs.com/package/vue3-editor" rel="noopener noreferrer" target="_blank">vue3-editor</a> built for the modern web. With its modular architecture and expressive API, it is completely customizable to fit any need.</h4><p><br></p><ol><li>Write text select and edit with multiple options.</li><li>This is quill snow editor.</li><li>Easy to customize and flexible.</li></ol><p><br></p><h4>Vue3 officially supports a standard toolbar theme and a depends on quill.</h4><p><br></p>',V={__name:"vue-editor",setup(k){const n={title:"Forms",currentpage:"Vue Editor",activepage:"Vue Editor"},c=[["bold","italic","underline"],[{list:"ordered"},{list:"bullet"}],["image","code-block"]];return(y,t)=>{const i=m("vue-editor",!0);return f(),b(h,null,[e(u,{propData:n}),o("div",w,[o("div",g,[e(l,{code:_,title:"Default Editor"},{default:a(()=>[e(i,{modelValue:s,"onUpdate:modelValue":t[0]||(t[0]=r=>s=r)})]),_:1},8,["code"])])]),o("div",x,[o("div",T,[e(l,{code:v,title:"Custom Toolbar"},{default:a(()=>[e(i,{modelValue:d,"onUpdate:modelValue":t[1]||(t[1]=r=>d=r),editorToolbar:c})]),_:1},8,["code"])])])],64)}}},C=p(V,[["__scopeId","data-v-a19a8d67"]]);export{C as default};
