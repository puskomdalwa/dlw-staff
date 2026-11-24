import{_ as g}from"./pageheader-D6ztwYLq.js";import{S as a}from"./showcodeCard-PixQ5-k0.js";import{_ as h,c as l,e as n,f as d,g as o,w as e,F as c,s as u,C as p,t as w,j as r}from"./index-BaLp0ls6.js";let f={script:`
     <div class="btn-list d-flex align-items-center flex-wrap">
                <div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                        Dropdown button
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                        <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                    </ul>
                </div>
                <div class="dropdown">
                    <a class="btn btn-secondary dropdown-toggle" href="javascript:void(0);" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                        Dropdown link
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                        <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                    </ul>
                </div>
            </div>`},y={script:`
        <div class="btn-list">
                <div class="btn-group" v-for="(idx,index) in dropdownData.singleDropdownButtons" :key="index">
                    <button type="button" :class="btn btn-{idx.class} dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        Action
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                    </ul>
                </div>
            </div>
    `,data:`
singleDropdownButtons = [
        { id: 1, class: "primary" },
        { id: 2, class: "secondary" },
        { id: 3, class: "success" },
        { id: 4, class: "info" },
        { id: 5, class: "warning" },
        { id: 6, class: "danger" }
    ]
`},x={script:`
    <div class="btn-list">
                <div class="btn-group" v-for="(idx,index) in dropdownData.singleDropdownButtons" :key="index">
                    <button type="button" :class="btn btn-{idx.class} dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                        Action
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                    </ul>
                </div>
            </div>`,data:`
        singleDropdownButtons = [
        { id: 1, class: "primary" },
        { id: 2, class: "secondary" },
        { id: 3, class: "success" },
        { id: 4, class: "info" },
        { id: 5, class: "warning" },
        { id: 6, class: "danger" }
    ]
        `},j={script:`
   <div class="btn-list">
                <div class="btn-group" v-for="(idx,index) in dropdownData.OutlineButtons" :key="index">
                    <button type="button" :class="btn btn-{idx.class} dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        Action
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                    </ul>
                </div>
            </div>`,data:`
            OutlineButtons = [
    { id: 1, class: "outline-primary" },
    { id: 2, class: "outline-secondary" },
    { id: 3, class: "outline-success" },
    { id: 4, class: "outline-info" },
    { id: 5, class: "outline-warning" },
    { id: 6, class: "outline-danger" }
]
            `},A={script:`
     <div class="btn-list">
                <div class="btn-group" v-for="(idx,index) in dropdownData.OutlineButtons" :key="index">
                    <button type="button" :class="btn btn-{idx.class} dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                        Action
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                    </ul>
                </div>
            </div>`,data:`
        OutlineButtons = [
    { id: 1, class: "outline-primary" },
    { id: 2, class: "outline-secondary" },
    { id: 3, class: "outline-success" },
    { id: 4, class: "outline-info" },
    { id: 5, class: "outline-warning" },
    { id: 6, class: "outline-danger" }
]
        `},S={script:`
    <div class="btn-group my-1" v-for="(idx,index) in dropdownData.SplitButtonsdata" :key="index">
                <button type="button" :class="btn btn-{idx.class}">Action</button>
                <button type="button" :class="btn btn-{idx.class} dropdown-toggle dropdown-toggle-split me-2" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="visually-hidden">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                </ul>
            </div>`,data:`
             SplitButtonsdata = [
        { id: 1, class: "primary" },
        { id: 2, class: "secondary" },
        { id: 3, class: "info" },
        { id: 4, class: "success" },
        { id: 5, class: "warning" },
        { id: 6, class: "danger" }
    ]
            `},_={script:`
    <div class="btn-group my-1 me-2">
                    <button class="btn btn-primary btn-lg dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Large button
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                    </ul>
                </div>
                <div class="btn-group my-1 me-2">
                    <button class="btn btn-light btn-lg" type="button">
                        Large split button
                    </button>
                    <button type="button" class="btn btn-lg btn-light dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                    </ul>
                </div>
                <!-- samll button groups (default and split) -->
                <div class="btn-group my-1 me-2">
                    <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Small button
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                    </ul>
                </div>
                <div class="btn-group my-1">
                    <button class="btn btn-light btn-sm" type="button">
                        Small split button
                    </button>
                    <button type="button" class="btn btn-sm btn-light dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                    </ul>
                </div>`},D={script:`
    <div class="btn-group dropup my-1">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        Dropup
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                    </ul>
                </div>
                <div class="btn-group dropup my-1">
                    <button type="button" class="btn btn-info">
                        Split dropup
                    </button>
                    <button type="button" class="btn btn-info dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                    </ul>
                </div>`},k={script:`
    <div class="btn-group dropend my-1">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        Dropright
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                    </ul>
                </div>
                <div class="btn-group dropend my-1">
                    <button type="button" class="btn btn-info">
                        Split dropend
                    </button>
                    <button type="button" class="btn btn-info dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">Toggle Dropright</span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                    </ul>
                </div>`},B={script:`
    <div class="btn-group dropstart my-1">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        Dropleft
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                    </ul>
                </div>
                <div class="btn-group">
                    <div class="btn-group dropstart my-1" role="group">
                        <button type="button" class="btn btn-info dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden">Toggle Dropstart</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-info my-1">
                        Split dropleft
                    </button>
                </div>`},M={script:`
    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    Dropstart
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="javascript:void(0);">Regular link</a></li>
                    <li><a class="dropdown-item active" href="javascript:void(0);" aria-current="true">Active
                            link</a>
                    </li>
                    <li><a class="dropdown-item" href="javascript:void(0);">Another link</a></li>
                </ul>`},C={script:`
    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                Dropstart
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="javascript:void(0);">Regular link</a></li>
                <li><a class="dropdown-item disabled" href="javascript:void(0);" aria-current="true">Active
                        link</a></li>
                <li><a class="dropdown-item" href="javascript:void(0);">Another link</a></li>
            </ul>`},R={script:`
    <div class="btn-list" >
                <div class="btn-group" v-for="(idx,index) in dropdownData.AutocloseButtons" :key="index">
                    <button :class="btn btn-{idx.class} dropdown-toggle" type="button" id="defaultDropdown" data-bs-toggle="dropdown" :data-bs-auto-close="idx.autoClose" aria-expanded="false">
                        {{idx.text}}
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="defaultDropdown">
                        <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                    </ul>
                </div>
            </div>`,data:`
             AutocloseButtons = [
        { id: 1, class: "primary", text: "Default dropdown",autoClose:true },
        { id: 2, class: "secondary", text: "Clickable  outside",autoClose:'inside' },
        { id: 3, class: "info", text: "Clickable  inside",autoClose:'outside' },
        { id: 4, class: "warning", text: "Manual  close" ,autoClose:false},
    ]
            `},T={script:`
    <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenu2" data-bs-toggle="dropdown" aria-expanded="false">
                        Dropdown
                    </button>
                    <div class="dropdown-menu">
                        <form class="px-4 py-3" novalidate>
                            <div class="mb-3">
                                <label for="exampleDropdownFormEmail1" class="form-label">Email
                                    address</label>
                                <input type="email" class="form-control" id="exampleDropdownFormEmail1" placeholder="email@example.com">
                            </div>
                            <div class="mb-3">
                                <label for="exampleDropdownFormPassword1" class="form-label">Password</label>
                                <input type="password" class="form-control" id="exampleDropdownFormPassword1" placeholder="Password">
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="dropdownCheck">
                                    <label class="form-check-label" for="dropdownCheck">
                                        Remember me
                                    </label>
                                </div>
                            </div>
                            <button class="btn btn-primary">Sign in</button>
                        </form>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="javascript:void(0);">New around here? Sign up</a>
                        <a class="dropdown-item" href="javascript:void(0);">Forgot password?</a>
                    </div>
                </div>`},L={script:`
     <p class="card-title mb-3">Use <code>.dropdown-center</code> on the parent element.
            </p>
            <div class="dropdown-center">
                <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownCenterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    Centered dropdown
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownCenterBtn">
                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);">Action two</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);">Action three</a></li>
                </ul>
            </div>`},O={script:`
     <p class="card-title mb-3">Use <code>.dropup-center</code>
                on the parent element.
            </p>
            <div class="dropup-center dropup">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropupCenterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    Centered dropup
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropupCenterBtn">
                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);">Action two</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);">Action three</a></li>
                </ul>
            </div>`},$={script:`
    <p class="card-title mb-3">You can use <code>&lt;a&gt;</code> or <code>&lt;button&gt;</code> as
                    dropdown items.</p>
                <div class="dropdown">
                    <button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenu1" data-bs-toggle="dropdown" aria-expanded="false">
                        Dropdown
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                        <li><button class="dropdown-item" type="button">Action</button></li>
                        <li><button class="dropdown-item" type="button">Another action</button>
                        </li>
                        <li><button class="dropdown-item" type="button">Something else
                                here</button>
                        </li>
                    </ul>
                </div>`},F={script:`
   <p class="card-title mb-3">Use <code>data-bs-offset</code> or <code>data-bs-reference</code> to change
                    the location of the dropdown.</p>
                <div class="d-flex align-items-center">
                    <div class="dropdown me-1">
                        <button type="button" class="btn btn-primary dropdown-toggle" id="dropdownMenuOffset" data-bs-toggle="dropdown" aria-expanded="false" data-bs-offset="10,20">
                            Offset
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuOffset">
                            <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                        </ul>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-info">Reference</button>
                        <button type="button" class="btn btn-info dropdown-toggle dropdown-toggle-split" id="dropdownMenuReference" data-bs-toggle="dropdown" aria-expanded="false" data-bs-reference="parent">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuReference">
                            <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                        </ul>
                    </div>
                </div>`},P={script:`
   <div class="btn-list">
                <div :class="btn-group {idx.drop}" v-for="(idx,index) in dropdownData.AlignmentButtons" :key="index">
                    <button :class="btn btn-{idx.class} dropdown-toggle mb-0" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                       {{idx.text}}
                    </button>
                    <ul :class="idx.dir" aria-labelledby="dropdownMenuButton">
                        <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                    </ul>
                </div>
            </div>`,data:`
         AlignmentButtons = [
        { id: 1, class: "primary", text: "Dropdown", dir: "dropdown-menu",drop:'' },
        { id: 2, class: "secondary", text: "Right-aligned menu", dir: "dropdown-menu dropdown-menu-end",drop:'' },
        { id: 3, class: "info", text: "Left, right-aligned lg", dir: "dropdown-menu dropdown-menu-lg-end",drop:'' },
        { id: 4, class: "warning", text: "Right, left-aligned lg", dir: "dropdown-menu dropdown-menu-end dropdown-menu-lg-start",drop:'' },
        { id: 5, class: "success", text: "Dropstart", dir: "dropdown-menu",drop:'dropstart' },
        { id: 6, class: "danger", text: "Dropend", dir: "dropdown-menu" ,drop:'dropend'},
        { id: 7, class: "teal", text: "Dropup", dir: "dropdown-menu" ,drop:'dropup'}
    ]
        `},E={script:`
    <div class="dropdown">
                <button class="btn btn-dark dropdown-toggle" type="button" id="dropdownMenuButton3" data-bs-toggle="dropdown" aria-expanded="false">
                    Dropdown button
                </button>
                <ul class="dropdown-menu dropdown-menu-dark">
                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                </ul>
            </div>`},U={script:`
     <div class="btn-group">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        Right-aligned menu example
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><button class="dropdown-item" type="button">Action</button>
                        </li>
                        <li><button class="dropdown-item" type="button">Another
                                action</button></li>
                        <li><button class="dropdown-item" type="button">Something else
                                here</button>
                        </li>
                    </ul>
                </div>`},N={script:`
     <div class="btn-group">
                    <button type="button" class="btn btn-secondary dropdown-toggle text-wrap" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                        Left-aligned but right aligned when large screen
                    </button>
                    <ul class="dropdown-menu dropdown-menu-lg-end">
                        <li><button class="dropdown-item" type="button">Action</button>
                        </li>
                        <li><button class="dropdown-item" type="button">Another
                                action</button></li>
                        <li><button class="dropdown-item" type="button">Something else
                                here</button>
                        </li>
                    </ul>
                </div>`},G={script:`
    <div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle text-wrap" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                        Left-aligned but right aligned when large screen
                    </button>
                    <ul class="dropdown-menu dropdown-menu-lg-start">
                        <li><button class="dropdown-item" type="button">Action</button>
                        </li>
                        <li><button class="dropdown-item" type="button">Another
                                action</button></li>
                        <li><button class="dropdown-item" type="button">Something else
                                here</button></li>
                    </ul>
                </div>`},H={script:`
   <div class="btn-list">
                <div class="btn-group" v-for="(idx,index) in dropdownData.CustomButtons" :key="index">
                    <button :class="btn btn-{idx.class} dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        {{idx.text}}
                    </button>
                    <ul :class="dropdown-menu {idx.class1}">
                        <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                    </ul>
                </div>
            </div>`,data:`
        CustomButtons = [
        { id: 1, class: "primary", text: "Primary", class1: "dropdown-menu-primary" },
        { id: 2, class: "secondary", text: "Secondary", class1: "dropdown-menu-secondary" },
        { id: 3, class: "warning", text: "warning", class1: "dropmenu-item-warning" },
        { id: 4, class: "info", text: "info", class1: "dropmenu-item-info" },
        { id: 5, class: "success-light", text: "success", class1: "dropmenu-light-success" },
        { id: 6, class: "danger-light", text: "danger", class1: "dropmenu-light-danger" }
    ]
        `},I={script:`
   <div class="btn-list">
                <div class="btn-group" v-for="(idx,index) in dropdownData.GhostButtons" :key="index">
                    <button type="button" :class="btn btn-{idx.class} dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        {{idx.text}}
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                    </ul>
                </div>
            </div>`,data:`
        GhostButtons = [
        { id: 1, class: "primary-ghost", text: "Primary" },
        { id: 2, class: "secondary-ghost", text: "Secondary" },
        { id: 3, class: "warning-ghost", text: "warning" },
        { id: 4, class: "info-ghost", text: "info" },
        { id: 5, class: "success-ghost", text: "success" },
        { id: 6, class: "danger-ghost", text: "danger" }
    ]
        `},z={script:`
    <p class="card-title mb-3">Use <code>.dropdown-item-text.</code> to create non-interactive dropdown items.</p>
                <div class="bd-example">
                    <ul class="dropdown-menu">
                        <li><span class="dropdown-item-text">Dropdown item text</span>
                        </li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a>
                        </li>
                    </ul>
                </div>`},V={script:`
   <p class="card-titlte mb-3">Add a <code>.dropdown-header</code> to label sections of actions in any dropdown menu.</p>
                <div class="bd-example">
                    <ul class="dropdown-menu">
                        <li>
                            <h6 class="dropdown-header">Dropdown header</h6>
                        </li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                    </ul>
                </div>`},Y={script:`
    <div class="bd-example">
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-header" href="javascript:void(0);">Heading</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                    </ul>
                </div>`},W={script:`
    <div class="bd-example">
                    <div class="dropdown-menu p-4 text-muted" style="max-width: 200px;">
                        <p>
                            Some example text that's free-flowing within the dropdown menu.
                        </p>
                        <p class="mb-0">
                            And this is more example text.
                        </p>
                    </div>
                </div>`};const v=[{id:1,class:"primary"},{id:2,class:"secondary"},{id:3,class:"success"},{id:4,class:"info"},{id:5,class:"warning"},{id:6,class:"danger"}],b=[{id:1,class:"outline-primary"},{id:2,class:"outline-secondary"},{id:3,class:"outline-success"},{id:4,class:"outline-info"},{id:5,class:"outline-warning"},{id:6,class:"outline-danger"}],q=[{id:1,class:"primary"},{id:2,class:"secondary"},{id:3,class:"info"},{id:4,class:"success"},{id:5,class:"warning"},{id:6,class:"danger"}],J=[{id:1,class:"primary",text:"Default dropdown",autoClose:!0},{id:2,class:"secondary",text:"Clickable  outside",autoClose:"inside"},{id:3,class:"info",text:"Clickable  inside",autoClose:"outside"},{id:4,class:"warning",text:"Manual  close",autoClose:!1}],K=[{id:1,class:"primary",text:"Primary",class1:"dropdown-menu-primary"},{id:2,class:"secondary",text:"Secondary",class1:"dropdown-menu-secondary"},{id:3,class:"warning",text:"warning",class1:"dropmenu-item-warning"},{id:4,class:"info",text:"info",class1:"dropmenu-item-info"},{id:5,class:"success-light",text:"success",class1:"dropmenu-light-success"},{id:6,class:"danger-light",text:"danger",class1:"dropmenu-light-danger"}],Q=[{id:1,class:"primary-ghost",text:"Primary"},{id:2,class:"secondary-ghost",text:"Secondary"},{id:3,class:"warning-ghost",text:"warning"},{id:4,class:"info-ghost",text:"info"},{id:5,class:"success-ghost",text:"success"},{id:6,class:"danger-ghost",text:"danger"}],X=[{id:1,class:"primary",text:"Dropdown",dir:"dropdown-menu",drop:""},{id:2,class:"secondary",text:"Right-aligned menu",dir:"dropdown-menu dropdown-menu-end",drop:""},{id:3,class:"info",text:"Left, right-aligned lg",dir:"dropdown-menu dropdown-menu-lg-end",drop:""},{id:4,class:"warning",text:"Right, left-aligned lg",dir:"dropdown-menu dropdown-menu-end dropdown-menu-lg-start",drop:""},{id:5,class:"success",text:"Dropstart",dir:"dropdown-menu",drop:"dropstart"},{id:6,class:"danger",text:"Dropend",dir:"dropdown-menu",drop:"dropend"},{id:7,class:"teal",text:"Dropup",dir:"dropdown-menu",drop:"dropup"}],Z={class:"row"},oo={class:"col-xl-12"},to={class:"row"},ao={class:"col-xl-12"},eo={class:"btn-list"},so={class:"col-xl-12"},lo={class:"btn-list"},no={class:"row"},io={class:"col-xl-12"},ro={class:"btn-list"},po={class:"col-xl-12"},co={class:"btn-list"},uo={class:"row"},wo={class:"col-xl-12"},vo={class:"col-xl-12"},bo={class:"row"},mo={class:"col-xl-6"},go={class:"col-xl-6"},ho={class:"col-xl-6"},fo={class:"col-xl-6"},yo={class:"row"},xo={class:"col-xl-3"},jo={class:"col-xl-6"},Ao={class:"btn-list"},So=["data-bs-auto-close"],_o={class:"col-xl-3"},Do={class:"row"},ko={class:"col-xl-6"},Bo={class:"col-xl-6"},Mo={class:"col-xl-6"},Co={class:"col-xl-6"},Ro={class:"row"},To={class:"col-xl-9"},Lo={class:"btn-list"},Oo={class:"col-xl-3"},$o={class:"row"},Fo={class:"col-xl-12"},Po={class:"col-xl-12"},Eo={class:"col-xl-12"},Uo={class:"row"},No={class:"col-xl-12"},Go={class:"btn-list"},Ho={class:"col-xl-12"},Io={class:"btn-list"},zo={class:"row"},Vo={class:"col-xl-6"},Yo={class:"col-xl-6"},Wo={class:"col-xl-6"},qo={class:"col-xl-6"},Jo={__name:"dropdowns",setup(Ko){const m={title:"Ui Elements",currentpage:"Dropdowns",activepage:"Dropdowns"};return(Qo,t)=>(n(),l(c,null,[d(g,{propData:m}),o("div",Z,[o("div",oo,[d(a,{title:"Dropdowns",code:f},{default:e(()=>t[0]||(t[0]=[o("div",{class:"btn-list d-flex align-items-center flex-wrap"},[o("div",{class:"dropdown"},[o("button",{class:"btn btn-primary dropdown-toggle",type:"button",id:"dropdownMenuButton1","data-bs-toggle":"dropdown","aria-expanded":"false"}," Dropdown button "),o("ul",{class:"dropdown-menu","aria-labelledby":"dropdownMenuButton1"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")])])]),o("div",{class:"dropdown"},[o("a",{class:"btn btn-secondary dropdown-toggle",href:"javascript:void(0);",role:"button",id:"dropdownMenuLink","data-bs-toggle":"dropdown","aria-expanded":"false"}," Dropdown link "),o("ul",{class:"dropdown-menu","aria-labelledby":"dropdownMenuLink"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")])])])],-1)])),_:1,__:[0]},8,["code"])])]),o("div",to,[o("div",ao,[d(a,{title:"Single dropdown buttons",code:y},{default:e(()=>[o("div",eo,[(n(!0),l(c,null,u(v,(s,i)=>(n(),l("div",{class:"btn-group",key:i},[o("button",{type:"button",class:p(`btn btn-${s.class} dropdown-toggle`),"data-bs-toggle":"dropdown","aria-expanded":"false"}," Action ",2),t[1]||(t[1]=o("ul",{class:"dropdown-menu"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")]),o("li",null,[o("hr",{class:"dropdown-divider"})]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Separated link")])],-1))]))),128))])]),_:1},8,["code"])]),o("div",so,[d(a,{title:"Rounded Button Dropdowns",code:x},{default:e(()=>[o("div",lo,[(n(!0),l(c,null,u(v,(s,i)=>(n(),l("div",{class:"btn-group",key:i},[o("button",{type:"button",class:p(`btn btn-${s.class} dropdown-toggle rounded-pill`),"data-bs-toggle":"dropdown","aria-expanded":"false"}," Action ",2),t[2]||(t[2]=o("ul",{class:"dropdown-menu"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")]),o("li",null,[o("hr",{class:"dropdown-divider"})]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Separated link")])],-1))]))),128))])]),_:1},8,["code"])])]),o("div",no,[o("div",io,[d(a,{title:"Outline Button Dropdowns",code:j},{default:e(()=>[o("div",ro,[(n(!0),l(c,null,u(b,(s,i)=>(n(),l("div",{class:"btn-group",key:i},[o("button",{type:"button",class:p(`btn btn-${s.class} dropdown-toggle`),"data-bs-toggle":"dropdown","aria-expanded":"false"}," Action ",2),t[3]||(t[3]=o("ul",{class:"dropdown-menu"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")]),o("li",null,[o("hr",{class:"dropdown-divider"})]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Separated link")])],-1))]))),128))])]),_:1},8,["code"])]),o("div",po,[d(a,{title:"Rounded Outline Dropdowns",code:A},{default:e(()=>[o("div",co,[(n(!0),l(c,null,u(b,(s,i)=>(n(),l("div",{class:"btn-group",key:i},[o("button",{type:"button",class:p(`btn btn-${s.class} dropdown-toggle rounded-pill`),"data-bs-toggle":"dropdown","aria-expanded":"false"}," Action ",2),t[4]||(t[4]=o("ul",{class:"dropdown-menu"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")]),o("li",null,[o("hr",{class:"dropdown-divider"})]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Separated link")])],-1))]))),128))])]),_:1},8,["code"])])]),o("div",uo,[o("div",wo,[d(a,{title:"Split buttons",code:S},{default:e(()=>[(n(!0),l(c,null,u(q,(s,i)=>(n(),l("div",{class:"btn-group my-1",key:i},[o("button",{type:"button",class:p(`btn btn-${s.class}`)},"Action",2),o("button",{type:"button",class:p(`btn btn-${s.class} dropdown-toggle dropdown-toggle-split me-2`),"data-bs-toggle":"dropdown","aria-expanded":"false"},t[5]||(t[5]=[o("span",{class:"visually-hidden"},"Toggle Dropdown",-1)]),2),t[6]||(t[6]=o("ul",{class:"dropdown-menu"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")]),o("li",null,[o("hr",{class:"dropdown-divider"})]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Separated link")])],-1))]))),128))]),_:1},8,["code"])]),o("div",vo,[d(a,{title:"Dropdown Sizing",code:_},{default:e(()=>t[7]||(t[7]=[o("div",{class:"btn-group my-1 me-2"},[o("button",{class:"btn btn-primary btn-lg dropdown-toggle",type:"button","data-bs-toggle":"dropdown","aria-expanded":"false"}," Large button "),o("ul",{class:"dropdown-menu"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")]),o("li",null,[o("hr",{class:"dropdown-divider"})]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Separated link")])])],-1),o("div",{class:"btn-group my-1 me-2"},[o("button",{class:"btn btn-light btn-lg",type:"button"}," Large split button "),o("button",{type:"button",class:"btn btn-lg btn-light dropdown-toggle dropdown-toggle-split","data-bs-toggle":"dropdown","aria-expanded":"false"},[o("span",{class:"visually-hidden"},"Toggle Dropdown")]),o("ul",{class:"dropdown-menu"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")]),o("li",null,[o("hr",{class:"dropdown-divider"})]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Separated link")])])],-1),o("div",{class:"btn-group my-1 me-2"},[o("button",{class:"btn btn-primary btn-sm dropdown-toggle",type:"button","data-bs-toggle":"dropdown","aria-expanded":"false"}," Small button "),o("ul",{class:"dropdown-menu"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")]),o("li",null,[o("hr",{class:"dropdown-divider"})]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Separated link")])])],-1),o("div",{class:"btn-group my-1"},[o("button",{class:"btn btn-light btn-sm",type:"button"}," Small split button "),o("button",{type:"button",class:"btn btn-sm btn-light dropdown-toggle dropdown-toggle-split","data-bs-toggle":"dropdown","aria-expanded":"false"},[o("span",{class:"visually-hidden"},"Toggle Dropdown")]),o("ul",{class:"dropdown-menu"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")]),o("li",null,[o("hr",{class:"dropdown-divider"})]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Separated link")])])],-1)])),_:1,__:[7]},8,["code"])])]),o("div",bo,[o("div",mo,[d(a,{title:"Dropup",code:D},{default:e(()=>t[8]||(t[8]=[o("div",{class:"btn-group dropup my-1 me-2"},[o("button",{type:"button",class:"btn btn-primary dropdown-toggle","data-bs-toggle":"dropdown","aria-expanded":"false"}," Dropup "),o("ul",{class:"dropdown-menu"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")]),o("li",null,[o("hr",{class:"dropdown-divider"})]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Separated link")])])],-1),o("div",{class:"btn-group dropup my-1"},[o("button",{type:"button",class:"btn btn-info"}," Split dropup "),o("button",{type:"button",class:"btn btn-info dropdown-toggle dropdown-toggle-split","data-bs-toggle":"dropdown","aria-expanded":"false"},[o("span",{class:"visually-hidden"},"Toggle Dropdown")]),o("ul",{class:"dropdown-menu"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")]),o("li",null,[o("hr",{class:"dropdown-divider"})]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Separated link")])])],-1)])),_:1,__:[8]},8,["code"])]),o("div",go,[d(a,{title:"Dropup right",code:k},{default:e(()=>t[9]||(t[9]=[o("div",{class:"btn-group dropend my-1 me-2"},[o("button",{type:"button",class:"btn btn-primary dropdown-toggle","data-bs-toggle":"dropdown","aria-expanded":"false"}," Dropright "),o("ul",{class:"dropdown-menu"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")]),o("li",null,[o("hr",{class:"dropdown-divider"})]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Separated link")])])],-1),o("div",{class:"btn-group dropend my-1"},[o("button",{type:"button",class:"btn btn-info"}," Split dropend "),o("button",{type:"button",class:"btn btn-info dropdown-toggle dropdown-toggle-split","data-bs-toggle":"dropdown","aria-expanded":"false"},[o("span",{class:"visually-hidden"},"Toggle Dropright")]),o("ul",{class:"dropdown-menu"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")]),o("li",null,[o("hr",{class:"dropdown-divider"})]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Separated link")])])],-1)])),_:1,__:[9]},8,["code"])]),o("div",ho,[d(a,{title:"Active",code:M},{default:e(()=>t[10]||(t[10]=[o("button",{type:"button",class:"btn btn-primary dropdown-toggle","data-bs-toggle":"dropdown","aria-expanded":"false"}," Dropstart ",-1),o("ul",{class:"dropdown-menu"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Regular link")]),o("li",null,[o("a",{class:"dropdown-item active",href:"javascript:void(0);","aria-current":"true"},"Active link")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another link")])],-1)])),_:1,__:[10]},8,["code"])]),o("div",fo,[d(a,{title:"Dropup left",code:B},{default:e(()=>t[11]||(t[11]=[o("div",{class:"btn-group dropstart my-1 me-2"},[o("button",{type:"button",class:"btn btn-primary dropdown-toggle","data-bs-toggle":"dropdown","aria-expanded":"false"}," Dropleft "),o("ul",{class:"dropdown-menu"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")]),o("li",null,[o("hr",{class:"dropdown-divider"})]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Separated link")])])],-1),o("div",{class:"btn-group"},[o("div",{class:"btn-group dropstart my-1",role:"group"},[o("button",{type:"button",class:"btn btn-info dropdown-toggle dropdown-toggle-split","data-bs-toggle":"dropdown","aria-expanded":"false"},[o("span",{class:"visually-hidden"},"Toggle Dropstart")]),o("ul",{class:"dropdown-menu"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")]),o("li",null,[o("hr",{class:"dropdown-divider"})]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Separated link")])])]),o("button",{type:"button",class:"btn btn-info my-1"}," Split dropleft ")],-1)])),_:1,__:[11]},8,["code"])])]),o("div",yo,[o("div",xo,[d(a,{title:"Disabled",code:C},{default:e(()=>t[12]||(t[12]=[o("button",{type:"button",class:"btn btn-primary dropdown-toggle","data-bs-toggle":"dropdown","aria-expanded":"false"}," Dropstart ",-1),o("ul",{class:"dropdown-menu"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Regular link")]),o("li",null,[o("a",{class:"dropdown-item disabled",href:"javascript:void(0);","aria-current":"true"},"Active link")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another link")])],-1)])),_:1,__:[12]},8,["code"])]),o("div",jo,[d(a,{title:"Auto close behavior",code:R},{default:e(()=>[o("div",Ao,[(n(!0),l(c,null,u(J,(s,i)=>(n(),l("div",{class:"btn-group",key:i},[o("button",{class:p(`btn btn-${s.class} dropdown-toggle`),type:"button",id:"defaultDropdown","data-bs-toggle":"dropdown","data-bs-auto-close":s.autoClose,"aria-expanded":"false"},w(s.text),11,So),t[13]||(t[13]=o("ul",{class:"dropdown-menu","aria-labelledby":"defaultDropdown"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Menu item")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Menu item")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Menu item")])],-1))]))),128))])]),_:1},8,["code"])]),o("div",_o,[d(a,{title:"dropdowns with Forms",code:T},{default:e(()=>t[14]||(t[14]=[o("div",{class:"dropdown"},[o("button",{class:"btn btn-secondary dropdown-toggle",type:"button",id:"dropdownMenu2","data-bs-toggle":"dropdown","aria-expanded":"false"}," Dropdown "),o("div",{class:"dropdown-menu"},[o("form",{class:"px-4 py-3",novalidate:""},[o("div",{class:"mb-3"},[o("label",{for:"exampleDropdownFormEmail1",class:"form-label"},"Email address"),o("input",{type:"email",class:"form-control",id:"exampleDropdownFormEmail1",placeholder:"email@example.com"})]),o("div",{class:"mb-3"},[o("label",{for:"exampleDropdownFormPassword1",class:"form-label"},"Password"),o("input",{type:"password",class:"form-control",id:"exampleDropdownFormPassword1",placeholder:"Password"})]),o("div",{class:"mb-3"},[o("div",{class:"form-check"},[o("input",{type:"checkbox",class:"form-check-input",id:"dropdownCheck"}),o("label",{class:"form-check-label",for:"dropdownCheck"}," Remember me ")])]),o("button",{class:"btn btn-primary"},"Sign in")]),o("div",{class:"dropdown-divider"}),o("a",{class:"dropdown-item",href:"javascript:void(0);"},"New around here? Sign up"),o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Forgot password?")])],-1)])),_:1,__:[14]},8,["code"])])]),o("div",Do,[o("div",ko,[d(a,{title:"Dropdown menu centered",code:L},{default:e(()=>t[15]||(t[15]=[o("p",{class:"card-title mb-3"},[r("Use "),o("code",null,".dropdown-center"),r(" on the parent element. ")],-1),o("div",{class:"dropdown-center"},[o("button",{class:"btn btn-primary dropdown-toggle",type:"button",id:"dropdownCenterBtn","data-bs-toggle":"dropdown","aria-expanded":"false"}," Centered dropdown "),o("ul",{class:"dropdown-menu","aria-labelledby":"dropdownCenterBtn"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action two")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action three")])])],-1)])),_:1,__:[15]},8,["code"])]),o("div",Bo,[d(a,{title:"Dropup centered",code:O},{default:e(()=>t[16]||(t[16]=[o("p",{class:"card-title mb-3"},[r("Use "),o("code",null,".dropup-center"),r(" on the parent element. ")],-1),o("div",{class:"dropup-center dropup"},[o("button",{class:"btn btn-secondary dropdown-toggle",type:"button",id:"dropupCenterBtn","data-bs-toggle":"dropdown","aria-expanded":"false"}," Centered dropup "),o("ul",{class:"dropdown-menu","aria-labelledby":"dropupCenterBtn"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action two")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action three")])])],-1)])),_:1,__:[16]},8,["code"])]),o("div",Mo,[d(a,{title:"Menu items",code:$},{default:e(()=>t[17]||(t[17]=[o("p",{class:"card-title mb-3"},[r("You can use "),o("code",null,"<a>"),r(" or "),o("code",null,"<button>"),r(" as dropdown items.")],-1),o("div",{class:"dropdown"},[o("button",{class:"btn btn-info dropdown-toggle",type:"button",id:"dropdownMenu1","data-bs-toggle":"dropdown","aria-expanded":"false"}," Dropdown "),o("ul",{class:"dropdown-menu","aria-labelledby":"dropdownMenu1"},[o("li",null,[o("button",{class:"dropdown-item",type:"button"},"Action")]),o("li",null,[o("button",{class:"dropdown-item",type:"button"},"Another action")]),o("li",null,[o("button",{class:"dropdown-item",type:"button"},"Something else here")])])],-1)])),_:1,__:[17]},8,["code"])]),o("div",Co,[d(a,{title:"Dropdowns options",code:F},{default:e(()=>t[18]||(t[18]=[o("p",{class:"card-title mb-3"},[r("Use "),o("code",null,"data-bs-offset"),r(" or "),o("code",null,"data-bs-reference"),r(" to change the location of the dropdown.")],-1),o("div",{class:"d-flex align-items-center gap-2 flex-wrap"},[o("div",{class:"dropdown me-1"},[o("button",{type:"button",class:"btn btn-primary dropdown-toggle",id:"dropdownMenuOffset","data-bs-toggle":"dropdown","aria-expanded":"false","data-bs-offset":"10,20"}," Offset "),o("ul",{class:"dropdown-menu","aria-labelledby":"dropdownMenuOffset"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")])])]),o("div",{class:"btn-group"},[o("button",{type:"button",class:"btn btn-info"},"Reference"),o("button",{type:"button",class:"btn btn-info dropdown-toggle dropdown-toggle-split",id:"dropdownMenuReference","data-bs-toggle":"dropdown","aria-expanded":"false","data-bs-reference":"parent"},[o("span",{class:"visually-hidden"},"Toggle Dropdown")]),o("ul",{class:"dropdown-menu","aria-labelledby":"dropdownMenuReference"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")]),o("li",null,[o("hr",{class:"dropdown-divider"})]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Separated link")])])])],-1)])),_:1,__:[18]},8,["code"])])]),o("div",Ro,[o("div",To,[d(a,{title:"Alignment options",code:P},{default:e(()=>[o("div",Lo,[(n(!0),l(c,null,u(X,(s,i)=>(n(),l("div",{class:p(`btn-group ${s.drop}`),key:i},[o("button",{class:p(`btn btn-${s.class} dropdown-toggle mb-0`),type:"button",id:"dropdownMenuButton","data-bs-toggle":"dropdown","aria-expanded":"false"},w(s.text),3),o("ul",{class:p(s.dir),"aria-labelledby":"dropdownMenuButton"},t[19]||(t[19]=[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Menu item")],-1),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Menu item")],-1),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Menu item")],-1)]),2)],2))),128))])]),_:1},8,["code"])]),o("div",Oo,[d(a,{title:"Dark Dropdowns",code:E},{default:e(()=>t[20]||(t[20]=[o("div",{class:"dropdown"},[o("button",{class:"btn btn-dark dropdown-toggle",type:"button",id:"dropdownMenuButton3","data-bs-toggle":"dropdown","aria-expanded":"false"}," Dropdown button "),o("ul",{class:"dropdown-menu dropdown-menu-dark"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")])])],-1)])),_:1,__:[20]},8,["code"])])]),o("div",$o,[o("div",Fo,[d(a,{title:"Menu alignment",code:U},{default:e(()=>t[21]||(t[21]=[o("div",{class:"btn-group"},[o("button",{type:"button",class:"btn btn-primary dropdown-toggle","data-bs-toggle":"dropdown","aria-expanded":"false"}," Right-aligned example "),o("ul",{class:"dropdown-menu dropdown-menu-end"},[o("li",null,[o("button",{class:"dropdown-item",type:"button"},"Action")]),o("li",null,[o("button",{class:"dropdown-item",type:"button"},"Another action")]),o("li",null,[o("button",{class:"dropdown-item",type:"button"},"Something else here")])])],-1)])),_:1,__:[21]},8,["code"])]),o("div",Po,[d(a,{title:"Responsive alignment end",code:N},{default:e(()=>t[22]||(t[22]=[o("div",{class:"btn-group"},[o("button",{type:"button",class:"btn btn-secondary dropdown-toggle text-wrap","data-bs-toggle":"dropdown","data-bs-display":"static","aria-expanded":"false"}," Left-aligned but right aligned when large screen "),o("ul",{class:"dropdown-menu dropdown-menu-lg-end"},[o("li",null,[o("button",{class:"dropdown-item",type:"button"},"Action")]),o("li",null,[o("button",{class:"dropdown-item",type:"button"},"Another action")]),o("li",null,[o("button",{class:"dropdown-item",type:"button"},"Something else here")])])],-1)])),_:1,__:[22]},8,["code"])]),o("div",Eo,[d(a,{title:"Responsive alignment left",code:G},{default:e(()=>t[23]||(t[23]=[o("div",{class:"btn-group"},[o("button",{type:"button",class:"btn btn-info dropdown-toggle text-wrap","data-bs-toggle":"dropdown","data-bs-display":"static","aria-expanded":"false"}," Left-aligned but left aligned when large screen "),o("ul",{class:"dropdown-menu dropdown-menu-lg-start"},[o("li",null,[o("button",{class:"dropdown-item",type:"button"},"Action")]),o("li",null,[o("button",{class:"dropdown-item",type:"button"},"Another action")]),o("li",null,[o("button",{class:"dropdown-item",type:"button"},"Something else here")])])],-1)])),_:1,__:[23]},8,["code"])])]),o("div",Uo,[o("div",No,[d(a,{title:"Custom Dropdown Menu's",code:H},{default:e(()=>[o("div",Go,[(n(!0),l(c,null,u(K,(s,i)=>(n(),l("div",{class:"btn-group",key:i},[o("button",{class:p(`btn btn-${s.class} dropdown-toggle`),type:"button","data-bs-toggle":"dropdown","aria-expanded":"false"},w(s.text),3),o("ul",{class:p(`dropdown-menu ${s.class1}`)},t[24]||(t[24]=[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")],-1),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")],-1),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")],-1)]),2)]))),128))])]),_:1},8,["code"])]),o("div",Ho,[d(a,{title:"Ghost Button Dropdowns",code:I},{default:e(()=>[o("div",Io,[(n(!0),l(c,null,u(Q,(s,i)=>(n(),l("div",{class:"btn-group",key:i},[o("button",{type:"button",class:p(`btn btn-${s.class} dropdown-toggle`),"data-bs-toggle":"dropdown","aria-expanded":"false"},w(s.text),3),t[25]||(t[25]=o("ul",{class:"dropdown-menu"},[o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")]),o("li",null,[o("hr",{class:"dropdown-divider"})]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Separated link")])],-1))]))),128))])]),_:1},8,["code"])])]),o("div",zo,[o("div",Vo,[d(a,{title:"non-interactive dropdown items",code:z},{default:e(()=>t[26]||(t[26]=[o("p",{class:"card-title mb-3"},[r("Use "),o("code",null,".dropdown-item-text."),r(" to create non-interactive dropdown items.")],-1),o("div",{class:"bd-example"},[o("ul",{class:"dropdown-menu"},[o("li",null,[o("span",{class:"dropdown-item-text"},"Dropdown item text")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")])])],-1)])),_:1,__:[26]},8,["code"])]),o("div",Yo,[d(a,{title:"Dropdown Headers",code:V},{default:e(()=>t[27]||(t[27]=[o("p",{class:"card-titlte mb-3"},[r("Add a "),o("code",null,".dropdown-header"),r(" to label sections of actions in any dropdown menu.")],-1),o("div",{class:"bd-example"},[o("ul",{class:"dropdown-menu"},[o("li",null,[o("h6",{class:"dropdown-header"},"Dropdown header")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")])])],-1)])),_:1,__:[27]},8,["code"])]),o("div",Wo,[d(a,{title:"Dropdown Dividers",code:Y},{default:e(()=>t[28]||(t[28]=[o("div",{class:"bd-example"},[o("ul",{class:"dropdown-menu"},[o("li",null,[o("a",{class:"dropdown-header",href:"javascript:void(0);"},"Heading")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Another action")]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Something else here")]),o("li",null,[o("hr",{class:"dropdown-divider"})]),o("li",null,[o("a",{class:"dropdown-item",href:"javascript:void(0);"},"Separated link")])])],-1)])),_:1,__:[28]},8,["code"])]),o("div",qo,[d(a,{title:"Dropdown Menu Text",code:W},{default:e(()=>t[29]||(t[29]=[o("div",{class:"bd-example"},[o("div",{class:"dropdown-menu p-4 text-muted",style:{"max-width":"200px"}},[o("p",null," Some example text that's free-flowing within the dropdown menu. "),o("p",{class:"mb-0"}," And this is more example text. ")])],-1)])),_:1,__:[29]},8,["code"])])])],64))}},tt=h(Jo,[["__scopeId","data-v-f167b6bb"]]);export{tt as default};
