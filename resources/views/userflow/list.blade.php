<ul class="list-group">
    @foreach($data as $flow)
        <li class="list-group-item">
            <a href="/admin/userflows/{{$flow->id}}/new">
                {{ $flow->name }}
            </a>
        </li>
    @endforeach
</ul>
