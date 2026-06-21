import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';

import '../../core/constants/app_colors.dart';

/// Image réseau avec cache, placeholder et repli en cas d'erreur.
class AppNetworkImage extends StatelessWidget {
  final String? url;
  final double? width;
  final double? height;
  final BoxFit fit;
  final double radius;

  const AppNetworkImage({
    super.key,
    required this.url,
    this.width,
    this.height,
    this.fit = BoxFit.cover,
    this.radius = 8,
  });

  @override
  Widget build(BuildContext context) {
    final fallback = Container(
      width: width,
      height: height,
      color: AppColors.borderLight,
      child: const Icon(Iconsax.gallery, color: AppColors.secondary),
    );

    if (url == null || url!.isEmpty) {
      return ClipRRect(borderRadius: BorderRadius.circular(radius), child: fallback);
    }

    return ClipRRect(
      borderRadius: BorderRadius.circular(radius),
      child: CachedNetworkImage(
        imageUrl: url!,
        width: width,
        height: height,
        fit: fit,
        placeholder: (_, _) => Container(
          width: width,
          height: height,
          color: AppColors.borderLight,
        ),
        errorWidget: (_, _, _) => fallback,
      ),
    );
  }
}
