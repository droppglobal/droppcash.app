@extends('layouts.user')

@section('title')
   @lang('Support tickets')
@endsection

@section('breadcrumb')

@lang('Support tickets')
 
@endsection

@section('content')
     <div class="container-xl">
        <div class="row justify-content-center pb-5">
            <div class="col-lg-4">
               <div class="card">
                   <div class="card-body">
                    <div class="chatbox__list__wrapper">
                        <div class="d-flex justify-content-between py-4 border-bottom border--dark">
                            <h3 class=""><a href="javascript:void(0)">@lang('Tickets')<i class="fas fa-arrow-right "></i></a></h3>
                            <button class="btn btn-primary shadow-none" data-bs-toggle="modal" data-bs-target="#modelId"><i class="fas fa-plus me-2"></i> @lang('Open a Ticket')</button>
                        </div>
         
                        <ul class="chat__list nav-tab nav border-0">
                            @forelse ($tickets as $item)
                            <li>
                                <a class="chat__item {{request('messages') == $item->ticket_num ? 'active':''}}" href="{{filter('messages',$item->ticket_num)}}">
                                    <div class="item__inner">
                                        <div class="post__creator">
                                            <div class="post__creator-thumb d-flex justify-content-between">
                                                <span class="username">{{$item->ticket_num}} </span>
                                                @if ($item->status == 1)
                                                <small class="badge bg-danger">!</small>
                                                @endif
                                            </div>
                                            <div class="post__creator-content">
                                                <h4 class="name d-inline-block">{{$item->subject}}</h4>
                                            </div>
                                        </div>
                                        <ul class="chat__meta d-flex justify-content-between">
                                            <li><span class="last-msg"></span></li>
                                            <li><span class="last-chat-time">{{dateFormat($item->created_at,'d M Y')}}</span></li>
                                        </ul>
                                    </div>
                                </a>
                            </li>
                            @empty
                            <li>
                                <a class="chat__item">
                                    <div class="item__inner">
                                        <div class="post__creator text-center">
                                            @lang('No Tickets Available')
                                        </div>
                                    </div>
                                </a>
                            </li>
                            @endforelse
                        </ul>
                    </div>
                   </div>
                   @if ($tickets->hasPages())
                   <div class="card-footer">
                       {{$tickets->links()}}
                   </div>
                   @endif
               </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane show fade active" id="c1">
                                <div class="chat__msg">
                                    <div class="chat__msg-header py-2 border-bottom">
                                        <div class="post__creator align-items-center">
                                            
                                            <div class="post__creator-content">
                                                <h4 class="name d-inline-block">@lang('Ticket Number : #'){{request('messages')}}</h4>
                                            </div>
                                            <a class="profile-link" href="javascript:void(0)"></a>
                                        </div>
                                    </div>
                                    
                                    <div class="chat__msg-body">
                                        <ul class="msg__wrapper mt-3">
                                            @if (request('messages'))
                                                @forelse ($messages as $item)
                                                    @if ($item->admin_id == null)
                                                    <li class="outgoing__msg">
                                                        <div class="msg__item">
                                                            <div class="post__creator ">
                                                                <div class="post__creator-content">
                                                                    <p class="out__msg">{{__($item->message)}}</p>
                                                                    @if ($item->file)
                                                                        <div class="text-start">
                                                                            <a href="">{{$item->file}}</a>
                                                                        </div>
                                                                      @endif
                                                                    <span class="comment-date text--secondary">{{diffTime($item->created_at)}}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </li>
                                                    @else
                                                    <li class="incoming__msg">
                                                        <div class="msg__item">
                                                            <div class="post__creator">
                                                                <div class="post__creator-content">
                                                                    <p>{{__($item->message)}}</p>
                                                                    @if ($item->file)
                                                                        <div class="text-end ms-auto">
                                                                            <a href="">{{$item->file}}</a>
                                                                        </div>
                                                                    @endif
                                                                    <span class="comment-date text--secondary">{{diffTime($item->created_at)}}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </li>
                                                    @endif
                                                @empty
                                                <li class="incoming__msg">
                                                    <div class="msg__item">
                                                        <div class="post__creator">
                                                            <div class="post__creator-content">
                                                                <h4 class="text-center">@lang('No messages yet!!')</h4>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>
                                                @endforelse
                                            @else
                                            <li>
                                                <div class="msg__item">
                                                    <div class="post__creator ">
                                                        <div class="post__creator-content ">
                                                           <h4 class="text-center">@lang('No messages yet')</h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                            @endif
                                        </ul>
                                    </div>
                                    @if (request('messages'))
                                    <div class="chat__msg-footer">
                                        <form action="{{route('user.ticket.reply',request('messages'))}}" class="send__msg" method="post" enctype="multipart/form-data">
                                            @csrf
                                            <div class="input-group">
                                                <input id="upload-file" type="file" name="file" class="form-control d-none">
                                                <label class="-formlabel upload-file" for="upload-file"><i class="fas fa-cloud-upload-alt"></i>
                                            </div>
                                            <div class="input-group">
                                                <textarea class="form-control form--control shadow-none" name="message"></textarea>
                                                <button class="border-0 outline-0 send-btn" type="submit"><i class="fab fa-telegram-plane"></i></button>
                                            </div>
                                        </form>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
     </div>

        <div class="modal fade" id="modelId" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
               <form action="{{route('user.ticket.open')}}" method="POST">
                @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">@lang('Open a ticket')</h5> 
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label class="mb-2">@lang('Subject')</label>
                                <input class="form-control shadow-none " type="text" name="subject" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">@lang('Close')</button>
                            <button type="submit" class="btn btn-primary">@lang('Submit')</button>
                        </div>
                    </div>
               </form>
            </div>
        </div>
  
@endsection