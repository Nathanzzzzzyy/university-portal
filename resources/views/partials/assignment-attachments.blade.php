@if($files->isNotEmpty())
<div class="assignment-attachments"><h4>{{ $label }}</h4><ul>@foreach($files as $file)<li><a href="{{ route('attachments.download',$file) }}"><span class="attachment-type" aria-hidden="true">{{ strtoupper(pathinfo($file->name,PATHINFO_EXTENSION)) }}</span><span class="attachment-info"><b>{{ $file->name }}</b><small>{{ number_format($file->size / 1024, 1) }} KB &middot; Download</small></span><span aria-hidden="true">&darr;</span></a></li>@endforeach</ul></div>
@endif
