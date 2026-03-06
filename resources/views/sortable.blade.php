    <div id="sortable" class="{{ $treat_tags_as ?? '' }}">
        @if(isset($treat_tags_as) && $treat_tags_as == 'categories')

            @foreach($categories as $category)
                <?php $categoryApps = $category->children; ?>
                <div class="category item-containerz cat-{{ \Illuminate\Support\Str::slug($category->title) }}" data-name="{{ $category->title }}" data-id="{{ $category->id }}">
                <div class="title"><a href="{{ $category->link }}" style="{{ $category->colour ? 'color: ' . $category->colour .';' : '' }}">{{ $category->title }}</a></div>
                @foreach($categoryApps as $app)
                    @include('item')
                @endforeach
                </div>
            @endforeach

            @foreach(($apps ?? []) as $app)
                @include('item')
            @endforeach


        @else

            @foreach($apps as $app)
                @include('item')
            @endforeach
            @include('add')
        @endif

        
    </div>
