<div class="wrapper-in">

    <header>
        {{ partial('main/header') }}
    </header>

    {{ partial('main/menu') }}

    <div id="main">
        
        {{ content() }}

    </div>

    <footer>
        {{ partial('main/footer') }}
    </footer>

</div>
