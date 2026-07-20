<style>
    .hold-wrapper{
        position:relative;
        width:170px;
        height:170px;
    }

    .hold-btn{
        position:absolute;
        top:50%;
        left:50%;
        transform:translate(-50%,-50%);
        width:120px;
        height:120px;
        z-index:2;

        font-weight:700;
        font-size:18px;
        line-height:1.3;
    }

    .progress-ring{
        transform:rotate(-90deg);
    }

    .progress-bg{
        fill:none;
        stroke:#e9ecef;
        stroke-width:8;
    }

    .progress-circle{
        fill:none;
        stroke-width:8;
        stroke-linecap:round;

        stroke-dasharray:452;
        stroke-dashoffset:452;
    }

    .success{
        stroke:#198754;
    }

    .danger{
        stroke:#dc3545;
    }

    .hold-btn:active{
        transform:translate(-50%,-50%) scale(.96);
    }
</style>



<div class="container py-5">

    <div class="text-center mb-5">
        <h2 class="fw-bold">Galit ka pa rin ba?</h2>
        <p class="text-muted">
            Pindutin at i-hold ng 2 segundo ang iyong sagot.
        </p>
    </div>

    <div class="d-flex justify-content-center gap-5 flex-wrap">

        <!-- Hindi na galit -->
        <div class="hold-wrapper">

            <svg class="progress-ring" width="170" height="170">
                <circle class="progress-bg" cx="85" cy="85" r="72"></circle>
                <circle class="progress-circle success" cx="85" cy="85" r="72"></circle>
            </svg>

            <button class="btn btn-success rounded-circle hold-btn"
                    data-message="🥰 Yehey! Hindi na siya galit.">
                Hindi na<br>galit
            </button>

        </div>

        <!-- Galit pa rin -->
        <div class="hold-wrapper">

            <svg class="progress-ring" width="170" height="170">
                <circle class="progress-bg" cx="85" cy="85" r="72"></circle>
                <circle class="progress-circle danger" cx="85" cy="85" r="72"></circle>
            </svg>

            <button class="btn btn-danger rounded-circle hold-btn"
                    data-message="😅 Ay hala... galit pa rin pala.">
                Galit<br>pa rin
            </button>

        </div>

    </div>

</div>


<script>
    const HOLD_TIME = 2000;
    const CIRCUMFERENCE = 452;

    document.querySelectorAll(".hold-wrapper").forEach(wrapper=>{

        const button = wrapper.querySelector(".hold-btn");
        const progress = wrapper.querySelector(".progress-circle");

        let timer;
        let start;
        let holding=false;

        function startHold(){

            if(holding) return;

            holding=true;
            start=Date.now();

            timer=setInterval(()=>{

                const elapsed=Date.now()-start;
                const percent=Math.min(elapsed/HOLD_TIME,1);

                progress.style.strokeDashoffset=
                    CIRCUMFERENCE-(CIRCUMFERENCE*percent);

                if(percent>=1){

                    clearInterval(timer);
                    holding=false;

                    alert(button.dataset.message);

                    progress.style.strokeDashoffset=CIRCUMFERENCE;

                }

            },16);

        }

        function cancelHold(){

            if(!holding) return;

            clearInterval(timer);
            holding=false;

            progress.style.strokeDashoffset=CIRCUMFERENCE;

        }

        button.addEventListener("mousedown",startHold);
        button.addEventListener("mouseup",cancelHold);
        button.addEventListener("mouseleave",cancelHold);

        button.addEventListener("touchstart",(e)=>{
            e.preventDefault();
            startHold();
        });

        button.addEventListener("touchend",cancelHold);
        button.addEventListener("touchcancel",cancelHold);

    });
</script>